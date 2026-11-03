<?php

namespace App\Actions\Imports;

use App\Actions\Boards\CreateBoard;
use App\Enums\BoardRole;
use App\Enums\LabelColor;
use App\Models\Activity;
use App\Models\Board;
use App\Models\BoardImport;
use App\Models\Card;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Stack;
use App\Models\User;
use App\Support\FractionalIndex;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class ImportTrelloBoard
{
	/** @var array<string, User> Trello member id => Stackr user */
	private array $people = [];

	/** @var array<string, string> Trello member id => full name */
	private array $names = [];

	/** @var list<array{card_id: int, url: string, name: string, file_name: string, mime_type: string|null, user_id: int, date: string, cover: bool}> */
	private array $pending = [];

	public function __construct(private CreateBoard $createBoard) {}

	/**
	 * Builds a new board, owned by $owner, from a Trello board's JSON export:
	 * lists, cards, labels, checklists, comments and uploaded files, with
	 * their original dates. $people says which Stackr user each Trello
	 * member (by id) is; they join the board. Work by anyone not in it is
	 * credited to the owner, comments with the person's name in front.
	 * Uploaded files aren't fetched here: they go on the returned
	 * BoardImport for DownloadImportFile, one at a time. Links become a
	 * list in the description. Nothing is sent to watchers. If anything
	 * fails, the half-built board is removed.
	 *
	 * @param  array<string, mixed>  $export
	 * @param  array<string, User>  $people
	 * @return array{board: Board, import: BoardImport, cards: int, comments: int}
	 */
	public function handle(User $owner, array $export, array $people, ?string $name = null): array
	{
		$this->people = $people;
		$this->names = self::members($export);
		$this->pending = [];

		$board = $this->createBoard->handle($owner, array_filter([
			'name' => Str::limit($name ?? (string) ($export['name'] ?? 'Trello board'), 250, ''),
			'description' => ($export['desc'] ?? '') !== '' ? $export['desc'] : null,
		]));

		try {
			foreach (array_unique(array_map(fn (User $user) => $user->id, $this->people)) as $userId) {
				if ($userId !== $owner->id) {
					$board->members()->attach($userId, ['role' => BoardRole::Member->value]);
				}
			}

			$labels = $this->labels($board, $this->rows($export['labels'] ?? null));
			$stacks = $this->stacks($board, $this->rows($export['lists'] ?? null));
			$actions = $this->rows($export['actions'] ?? null);
			$checklists = $this->rows($export['checklists'] ?? null)->groupBy('idCard');
			$counts = ['cards' => 0, 'comments' => 0];

			$cards = $this->rows($export['cards'] ?? null)
				->filter(fn (array $card) => isset($stacks[$card['idList']]))
				->sortBy('pos')
				->groupBy('idList');

			foreach ($cards as $listId => $listCards) {
				$position = null;

				foreach ($listCards as $data) {
					$position = FractionalIndex::between($position, null);
					$card = $this->card($stacks[$listId], $data, $position, $owner, $actions);
					$card->labels()->attach($this->ids($data['idLabels'] ?? null)->map(fn ($id) => $labels[$id] ?? null)->filter()->unique()->all());
					$card->members()->attach($this->ids($data['idMembers'] ?? null)->map(fn ($id) => ($this->people[$id] ?? null)?->id)->filter()->unique()->all());
					$this->checklists($card, $checklists->get($data['id']) ?? new Collection);
					$counts['comments'] += $this->comments($card, $owner, $actions->where('type', 'commentCard')->where('data.card.id', $data['id']));
					$this->queueUploads($card, $owner, $data);
					$counts['cards']++;
				}
			}

			$import = new BoardImport(['source' => 'trello', 'pending' => $this->pending, 'failed' => [], 'total' => count($this->pending)]);
			$import->board()->associate($board);
			$import->user()->associate($owner);
			$import->save();
		} catch (Throwable $e) {
			$board->delete();

			throw $e;
		}

		return ['board' => $board, 'import' => $import, ...$counts];
	}

	/**
	 * The export's people, Trello member id => full name.
	 *
	 * @param  array<string, mixed>  $export
	 * @return array<string, string>
	 */
	public static function members(array $export): array
	{
		$names = [];

		foreach (is_array($export['members'] ?? null) ? $export['members'] : [] as $member) {
			if (is_array($member) && is_string($member['id'] ?? null)) {
				$names[$member['id']] = (string) ($member['fullName'] ?? $member['username'] ?? 'Someone');
			}
		}

		return $names;
	}

	private function person(?string $trelloId, User $owner): User
	{
		return ($trelloId !== null ? ($this->people[$trelloId] ?? null) : null) ?? $owner;
	}

	/**
	 * Credit line for work by someone without a Stackr account, empty otherwise.
	 */
	private function byline(?string $trelloId): string
	{
		if ($trelloId === null || ($this->people[$trelloId] ?? null) !== null) {
			return '';
		}

		return '**'.($this->names[$trelloId] ?? 'Someone on Trello').':** ';
	}

	/**
	 * @param  Collection<int, array<string, mixed>>  $labels
	 * @return array<string, int> Trello label id => label id
	 */
	private function labels(Board $board, Collection $labels): array
	{
		return $labels->mapWithKeys(fn (array $label) => [
			$label['id'] => $board->labels()->create([
				'name' => ($label['name'] ?? '') !== '' ? Str::limit($label['name'], 60, '') : null,
				'color' => self::labelColor($label['color'] ?? null),
			])->id,
		])->all();
	}

	/**
	 * Trello's colours, light and dark shades included, on Stackr's eight.
	 */
	public static function labelColor(?string $color): LabelColor
	{
		$base = Str::before((string) $color, '_');

		return LabelColor::tryFrom($base) ?? match ($base) {
			'lime' => LabelColor::Green,
			'pink' => LabelColor::Red,
			'black' => LabelColor::Gray,
			default => LabelColor::Gray,
		};
	}

	/**
	 * @param  Collection<int, array<string, mixed>>  $lists
	 * @return array<string, Stack> Trello list id => stack
	 */
	private function stacks(Board $board, Collection $lists): array
	{
		$stacks = [];
		$position = null;

		foreach ($lists->sortBy('pos') as $list) {
			$position = FractionalIndex::between($position, null);
			$stacks[$list['id']] = $board->stacks()->create([
				'name' => Str::limit((string) $list['name'], 250, ''),
				'position' => $position,
				'archived_at' => ($list['closed'] ?? false) ? now() : null,
			]);
		}

		return $stacks;
	}

	/**
	 * @param  array<string, mixed>  $data
	 * @param  Collection<int, array<string, mixed>>  $actions
	 */
	private function card(Stack $stack, array $data, string $position, User $owner, Collection $actions): Card
	{
		$created = self::idDate($data['id']);
		$lastActivity = isset($data['dateLastActivity']) ? Carbon::parse($data['dateLastActivity']) : $created;
		$links = $this->rows($data['attachments'] ?? null)->reject(fn (array $a) => $a['isUpload'] ?? false);
		$description = trim(implode("\n\n", array_filter([
			$data['desc'] ?? '',
			$links->isNotEmpty() ? $links->map(fn (array $a) => "- [{$a['name']}]({$a['url']})")->implode("\n") : '',
		])));

		$card = new Card([
			'title' => Str::limit((string) $data['name'], 250, ''),
			'description' => $description !== '' ? $description : null,
			'position' => $position,
			'start_at' => $data['start'] ?? null,
			'due_at' => $data['due'] ?? null,
			'completed_at' => ($data['dueComplete'] ?? false) ? $lastActivity : null,
			'archived_at' => ($data['closed'] ?? false) ? $lastActivity : null,
		]);
		$card->stack()->associate($stack);
		$card->created_at = $created;
		$card->updated_at = $lastActivity;
		$card->save();

		$creator = $actions->firstWhere(fn (array $a) => $a['type'] === 'createCard' && ($a['data']['card']['id'] ?? null) === $data['id']);
		self::activity($card, 'created', ['stack' => $stack->name], $this->person($creator['idMemberCreator'] ?? null, $owner), $created);

		return $card;
	}

	/**
	 * @param  Collection<int, array<string, mixed>>  $checklists
	 */
	private function checklists(Card $card, Collection $checklists): void
	{
		foreach ($checklists->sortBy('pos') as $data) {
			$checklist = $card->checklists()->create(['title' => Str::limit((string) $data['name'], 250, '')]);

			foreach ($this->rows($data['checkItems'] ?? null)->sortBy('pos') as $item) {
				$checklist->items()->create([
					'title' => Str::limit((string) $item['name'], 500, ''),
					'completed_at' => ($item['state'] ?? null) === 'complete' ? $card->updated_at : null,
				]);
			}
		}
	}

	/**
	 * @param  Collection<int, array<string, mixed>>  $actions
	 */
	private function comments(Card $card, User $owner, Collection $actions): int
	{
		foreach ($actions->sortBy('date') as $action) {
			$comment = new Comment(['body' => $this->byline($action['idMemberCreator'] ?? null).($action['data']['text'] ?? '')]);
			$comment->card()->associate($card);
			$comment->user()->associate($this->person($action['idMemberCreator'] ?? null, $owner));
			$comment->created_at = Carbon::parse($action['date']);
			$comment->updated_at = isset($action['data']['dateLastEdited']) ? Carbon::parse($action['data']['dateLastEdited']) : $comment->created_at;
			$comment->save();
		}

		return $actions->count();
	}

	/**
	 * Notes the card's uploaded files for DownloadImportFile, oldest first.
	 *
	 * @param  array<string, mixed>  $data
	 */
	private function queueUploads(Card $card, User $owner, array $data): void
	{
		$uploads = $this->rows($data['attachments'] ?? null)->filter(fn (array $a) => $a['isUpload'] ?? false)->sortBy('date');

		foreach ($uploads as $upload) {
			$this->pending[] = [
				'card_id' => $card->id,
				'url' => (string) $upload['url'],
				'name' => Str::limit((string) $upload['name'], 250, ''),
				'file_name' => (string) (($upload['fileName'] ?? '') ?: $upload['name']),
				'mime_type' => is_string($upload['mimeType'] ?? null) && $upload['mimeType'] !== '' ? $upload['mimeType'] : null,
				'user_id' => $this->person($upload['idMember'] ?? null, $owner)->id,
				'date' => (string) $upload['date'],
				'cover' => ($data['idAttachmentCover'] ?? null) === $upload['id'],
			];
		}
	}

	/**
	 * Logs a card event at its original time, by its original author.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public static function activity(Card $card, string $type, array $data, User $user, Carbon $at): void
	{
		$activity = new Activity(['type' => $type, 'data' => $data]);
		$activity->card()->associate($card);
		$activity->user()->associate($user);
		$activity->created_at = $at->toImmutable();
		$activity->save();
	}

	/**
	 * The arrays in a list from the export; anything else is dropped.
	 *
	 * @return Collection<int, array<string, mixed>>
	 */
	private function rows(mixed $value): Collection
	{
		/** @var list<array<string, mixed>> $rows */
		$rows = is_array($value) ? array_values(array_filter($value, is_array(...))) : [];

		return new Collection($rows);
	}

	/**
	 * @return Collection<int, string>
	 */
	private function ids(mixed $value): Collection
	{
		return new Collection(is_array($value) ? array_values(array_filter($value, is_string(...))) : []);
	}

	/**
	 * Trello ids are Mongo ObjectIds: the first eight hex digits are the creation time.
	 */
	public static function idDate(string $id): Carbon
	{
		return Carbon::createFromTimestampUTC(hexdec(substr($id, 0, 8)));
	}
}
