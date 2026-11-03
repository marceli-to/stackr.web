<?php

namespace App\Console\Commands;

use App\Actions\Imports\CreateImportedUser;
use App\Actions\Imports\DownloadImportFile;
use App\Actions\Imports\ImportTrelloBoard;
use App\Models\BoardImport;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * Turns a Trello board's JSON export (Trello: board menu → Print, export
 * and share → Export as JSON) into a new Stackr board, step by step:
 * pick the export from storage/, name the board and its owner, then say
 * who each Trello person is: an existing user, a new verified account
 * (no emails are sent), or nobody. Uploaded files are downloaded from
 * Trello with an API key and token from trello.com/power-ups/admin,
 * asked for or read from TRELLO_KEY / TRELLO_TOKEN.
 *
 * Without interaction, the defaults apply: people are matched by email
 * from --map, else by name, and nobody is created.
 */
#[Signature('app:import-trello
	{file? : The Trello JSON export, relative to storage/ or absolute}
	{--name= : Name of the new board (default: the Trello name)}
	{--owner= : Email of the Stackr user who will own the board}
	{--map=* : A Trello username or full name to a Stackr email, as "username=email"}
	{--key= : Trello API key (or TRELLO_KEY)}
	{--token= : Trello API token (or TRELLO_TOKEN)}')]
#[Description('Import a board from a Trello JSON export')]
class ImportTrello extends Command
{
	private const CREATE = '__create';

	private const NOBODY = '__nobody';

	/** @var list<string> */
	private array $created = [];

	private CreateImportedUser $createUser;

	public function handle(ImportTrelloBoard $import, DownloadImportFile $download, CreateImportedUser $createUser): int
	{
		$this->createUser = $createUser;

		$file = $this->file();

		if ($file === null) {
			return self::FAILURE;
		}

		$export = json_decode((string) file_get_contents($file), true);

		if (! is_array($export) || ! isset($export['lists'], $export['cards'], $export['members'])) {
			$this->error("{$file} is not a Trello board export.");

			return self::FAILURE;
		}

		$this->info('Trello board “'.($export['name'] ?? '').'”: '.count($export['lists']).' lists, '.count($export['cards']).' cards.');

		// 1. The board.
		$name = $this->option('name') ?: (string) ($export['name'] ?? 'Trello board');

		if ($this->input->isInteractive() && ! $this->option('name')) {
			$name = text('Name of the new board', default: $name, required: true);
		}
		$owner = $this->owner();

		if ($owner === null) {
			return self::FAILURE;
		}

		// 2. The people.
		$people = $this->people($export, $owner);

		// 3. The import.
		$key = $this->option('key') ?: (getenv('TRELLO_KEY') ?: null);
		$token = $this->option('token') ?: (getenv('TRELLO_TOKEN') ?: null);

		if ($this->input->isInteractive() && $this->hasUploads($export)) {
			$key ??= text('Trello API key (empty: skip the files)') ?: null;
			$token ??= $key !== null ? (password('Trello API token') ?: null) : null;
		}

		$this->info("Importing into “{$name}” for {$owner->name}…");
		$result = $import->handle($owner, $export, $people, $name);
		$this->info("Imported {$result['cards']} cards and {$result['comments']} comments.");

		$this->downloadFiles($result['import'], $download, $key, $token);

		foreach ($this->created as $line) {
			$this->line($line);
		}

		$this->line(route('boards.show', $result['board']));

		return self::SUCCESS;
	}

	/**
	 * The export: the argument, or a JSON file picked from storage/.
	 */
	private function file(): ?string
	{
		$file = $this->argument('file');

		if (is_string($file) && $file !== '') {
			$path = str_starts_with($file, '/') ? $file : storage_path($file);

			if (! is_file($path)) {
				$this->error("{$path} does not exist.");

				return null;
			}

			return $path;
		}

		$files = collect(File::allFiles(storage_path()))
			->filter(fn (SplFileInfo $f) => strtolower($f->getExtension()) === 'json' && ! str_starts_with($f->getRelativePathname(), 'framework/'))
			->mapWithKeys(fn (SplFileInfo $f) => [$f->getPathname() => $this->trelloName($f)])
			->filter()
			->map(fn (string $board, string $path) => Str::after($path, storage_path().'/')." ({$board})")
			->sort();

		if ($files->isEmpty()) {
			$this->error('No Trello export in storage/. Put the Trello export there, for example in storage/app/private/imports/.');

			return null;
		}

		return (string) select('Which Trello export?', $files->all(), scroll: 10);
	}

	private function owner(): ?User
	{
		$email = $this->option('owner');

		if (is_string($email) && $email !== '') {
			$owner = User::where('email', $email)->first();

			if ($owner === null) {
				$this->error("No Stackr user with the email {$email}.");
			}

			return $owner;
		}

		$users = User::orderBy('name')->get();

		if ($users->isEmpty() || ! $this->input->isInteractive()) {
			$this->error('Pass --owner with the email of an existing Stackr user.');

			return null;
		}

		$id = select('Who owns the board?', $users->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} <{$u->email}>"])->all());

		return $users->firstWhere('id', $id);
	}

	/**
	 * Which Stackr user each Trello person is, Trello member id => user.
	 *
	 * @param  array<string, mixed>  $export
	 * @return array<string, User>
	 */
	private function people(array $export, User $owner): array
	{
		$map = [];
		foreach ((array) $this->option('map') as $pair) {
			[$from, $to] = array_pad(explode('=', (string) $pair, 2), 2, '');
			$map[trim($from)] = trim($to);
		}

		$people = [];

		foreach ($export['members'] as $member) {
			$fullName = (string) ($member['fullName'] ?? $member['username'] ?? 'Someone');
			$email = $map[$member['username'] ?? ''] ?? $map[$fullName] ?? null;
			$guess = $email !== null
				? User::where('email', $email)->first()
				: User::where('name', $fullName)->first();

			if (! $this->input->isInteractive()) {
				if ($guess !== null) {
					$people[$member['id']] = $guess;
				}

				continue;
			}

			$options = [
				...User::orderBy('name')->get()->mapWithKeys(fn (User $u) => [(string) $u->id => "{$u->name} <{$u->email}>"])->all(),
				self::CREATE => 'Create a new account',
				self::NOBODY => "Nobody (credit their work to {$owner->name})",
			];

			$choice = select(
				"Trello: {$fullName} (@".($member['username'] ?? '?').') is…',
				$options,
				default: $guess !== null ? (string) $guess->id : self::CREATE,
				scroll: 10,
			);

			if ($choice === self::CREATE) {
				$people[$member['id']] = $this->createUser($fullName);
			} elseif ($choice !== self::NOBODY) {
				$people[$member['id']] = User::findOrFail((int) $choice);
			}
		}

		return $people;
	}

	/**
	 * A verified account with a random password; nothing is emailed.
	 */
	private function createUser(string $fullName): User
	{
		$name = text('Name', default: $fullName, required: true);
		$email = text('Email', required: true, validate: fn (string $value) => match (true) {
			filter_var($value, FILTER_VALIDATE_EMAIL) === false => 'That is not an email address.',
			User::where('email', $value)->exists() => 'A user with that email exists already; pick them from the list instead.',
			default => null,
		});
		['user' => $user, 'password' => $password] = $this->createUser->handle($name, $email);

		$this->created[] = "Created {$name} <{$email}> with the password {$password} (they can change it in Settings).";

		return $user;
	}

	/**
	 * Fetches the files one by one; without a key and token they stay out.
	 */
	private function downloadFiles(BoardImport $import, DownloadImportFile $download, ?string $key, ?string $token): void
	{
		if ($import->total === 0) {
			$import->delete();

			return;
		}

		if ($key === null || $token === null) {
			$this->warn("{$import->total} file(s) not downloaded (no Trello key and token).");
			$import->delete();

			return;
		}

		$bar = $this->output->createProgressBar($import->total);
		$bar->start();

		try {
			do {
				$result = $download->handle($import, $key, $token);
				$bar->setProgress($result['done']);
			} while (! $result['finished']);
		} catch (RuntimeException $e) {
			$this->newLine();
			$this->error($e->getMessage().' The rest of the files can be fetched from the import page in Stackr.');

			return;
		}

		$bar->finish();
		$this->newLine();

		foreach ($result['failed'] as $failed) {
			$this->warn("“{$failed['card']}”: could not download {$failed['name']} ({$failed['reason']}).");
		}

		$this->info(($result['total'] - count($result['failed']))." of {$result['total']} files downloaded.");
	}

	/**
	 * The board name when the file is a Trello export, else null.
	 */
	private function trelloName(SplFileInfo $file): ?string
	{
		if ($file->getSize() > 100 * 1024 * 1024) {
			return null;
		}

		$data = json_decode($file->getContents(), true);

		return is_array($data) && isset($data['lists'], $data['cards'], $data['members']) && is_string($data['name'] ?? null)
			? $data['name']
			: null;
	}

	/**
	 * @param  array<string, mixed>  $export
	 */
	private function hasUploads(array $export): bool
	{
		foreach ($export['cards'] as $card) {
			foreach ($card['attachments'] ?? [] as $attachment) {
				if ($attachment['isUpload'] ?? false) {
					return true;
				}
			}
		}

		return false;
	}
}
