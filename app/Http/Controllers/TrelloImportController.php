<?php

namespace App\Http\Controllers;

use App\Actions\Imports\CreateImportedUser;
use App\Actions\Imports\DownloadImportFile;
use App\Actions\Imports\ImportTrelloBoard;
use App\Http\Requests\ImportDownloadRequest;
use App\Http\Requests\TrelloImportRequest;
use App\Models\BoardImport;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class TrelloImportController extends Controller
{
	/**
	 * The wizard, with the people an import can be matched to and any
	 * earlier import whose files aren't all in yet.
	 */
	public function create(Request $request): Response
	{
		/** @var User $user */
		$user = $request->user();

		return Inertia::render('imports/Trello', [
			'users' => $user->knownUsers()->orderBy('name')->get(['users.id', 'name', 'email']),
			'canCreateUsers' => $user->isAdmin(),
			'unfinished' => BoardImport::with('board')->where('user_id', $user->id)->latest('id')->get()
				->map(fn (BoardImport $import) => [
					'id' => $import->id,
					'board' => ['key' => $import->board->key, 'name' => $import->board->name],
					'done' => $import->total - count($import->pending),
					'total' => $import->total,
				]),
		]);
	}

	/**
	 * Builds the board; its files come next, one request each.
	 */
	public function store(TrelloImportRequest $request, ImportTrelloBoard $import, CreateImportedUser $createUser): JsonResponse
	{
		/** @var User $owner */
		$owner = $request->user();
		$created = [];

		$result = DB::transaction(function () use ($request, $import, $createUser, $owner, &$created) {
			$people = [];

			foreach ((array) $request->validated('people', []) as $trelloId => $person) {
				if ($person['choice'] === 'user') {
					$people[$trelloId] = User::query()->whereKey($person['user_id'])->firstOrFail();
				} elseif ($person['choice'] === 'create') {
					['user' => $user, 'password' => $password] = $createUser->handle($person['name'], $person['email']);
					$people[$trelloId] = $user;
					$created[] = ['name' => $user->name, 'email' => $user->email, 'password' => $password];
				}
			}

			return $import->handle($owner, $request->export(), $people, $request->validated('name'));
		});

		return response()->json([
			'import' => ['id' => $result['import']->id, 'total' => $result['import']->total],
			'board' => ['key' => $result['board']->key, 'name' => $result['board']->name],
			'cards' => $result['cards'],
			'comments' => $result['comments'],
			'created' => $created,
		]);
	}

	/**
	 * Fetches the next file from Trello.
	 */
	public function download(ImportDownloadRequest $request, BoardImport $import, DownloadImportFile $download): JsonResponse
	{
		abort_unless($import->user_id === $request->user()?->id, 404);

		try {
			return response()->json($download->handle($import, $request->validated('key'), $request->validated('token')));
		} catch (RuntimeException $e) {
			throw ValidationException::withMessages(['token' => $e->getMessage()]);
		}
	}

	/**
	 * Leaves the remaining files out.
	 */
	public function destroy(Request $request, BoardImport $import): JsonResponse
	{
		abort_unless($import->user_id === $request->user()?->id, 404);

		$import->delete();

		return response()->json(['ok' => true]);
	}
}
