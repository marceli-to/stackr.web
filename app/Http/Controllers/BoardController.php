<?php

namespace App\Http\Controllers;

use App\Actions\Boards\CreateBoard;
use App\Actions\Boards\DeleteBoard;
use App\Actions\Boards\UpdateBoard;
use App\Http\Requests\BoardRequest;
use App\Http\Resources\ArchivedCardResource;
use App\Http\Resources\ArchivedStackResource;
use App\Http\Resources\BoardResource;
use App\Http\Resources\BoardTileResource;
use App\Http\Resources\CardResource;
use App\Http\Resources\DestinationResource;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\LabelResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\StackResource;
use App\Models\Board;
use App\Models\Checklist;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
	/**
	 * Show the boards the user is a member of.
	 */
	public function index(Request $request): Response
	{
		/** @var User $user */
		$user = $request->user();

		$memberships = $user->memberships()
			->with(['board' => fn ($query) => $query->withCount([
				'stacks' => fn ($query) => $query->active(),
				'cards' => fn ($query) => $query->active()->whereNull('stacks.archived_at'),
			])])
			->get()
			->sortBy('board.name', SORT_NATURAL | SORT_FLAG_CASE)
			->values();

		return Inertia::render('boards/Index', [
			'boards' => BoardTileResource::collection($memberships),
		]);
	}

	/**
	 * Create a board owned by the user.
	 */
	public function store(BoardRequest $request, CreateBoard $createBoard): RedirectResponse
	{
		/** @var User $user */
		$user = $request->user();

		$board = $createBoard->handle($user, $request->validated());

		$this->toast(__('Board created.'));

		return to_route('boards.show', $board);
	}

	/**
	 * Show a board. ?card=<id> opens a card; it has to be on this
	 * board or in its archive. The archive itself loads only when asked for.
	 */
	public function show(Request $request, Board $board): Response
	{
		Gate::authorize('view', $board);

		$board->load([
			'labels',
			'stacks' => fn ($query) => $query->active(),
			'stacks.cards' => fn ($query) => $query->active()->withCount(['attachments', 'comments']),
			'stacks.cards.labels:id',
			'stacks.cards.members:id',
			'stacks.cards.cover',
			// Only whether the viewer watches each card.
			'stacks.cards.watchers' => fn ($query) => $query->whereKey($request->user()?->id)->select('users.id'),
			'stacks.cards.checklists.items:id,checklist_id,completed_at',
		]);

		$open = $request->integer('card') ?: null;
		$card = $open !== null
			? $board->stacks->flatMap(fn (Stack $stack) => $stack->cards)->firstWhere('id', $open)
				?? $board->cards()->find($open)
			: null;

		$manages = $request->user()?->can('manageMembers', $board) ?? false;

		return Inertia::render('boards/Show', [
			'board' => new BoardResource($board),
			'labels' => LabelResource::collection($board->labels),
			'members' => MemberResource::collection($board->membershipsInOrder()),
			'invitations' => $manages ? InvitationResource::collection($board->invitations()->get()) : [],
			'canManageMembers' => $manages,
			'inviteLink' => $manages && $board->invite_token !== null ? route('invite-link.show', $board->invite_token) : null,
			'watching' => $request->user() !== null && $board->watchers()->whereKey($request->user()->id)->exists(),
			'stacks' => StackResource::collection($board->stacks),
			'card' => $card !== null ? new CardResource($card) : null,
			'destinations' => Inertia::optional(fn () => DestinationResource::collection(
				$request->user()?->boards()->with(['stacks' => fn ($query) => $query->active()->withCount(['cards' => fn ($query) => $query->active()])])->orderBy('name')->get() ?? [],
			)),
			// Checklists to copy items from, loaded when adding one.
			'checklists' => Inertia::optional(fn () => Checklist::query()
				->whereIn('card_id', $board->cards()->select('cards.id'))
				->with('card:id,title')
				->withCount('items')
				->get()
				->map(fn (Checklist $checklist): array => [
					'id' => $checklist->id,
					'title' => $checklist->title,
					'card' => $checklist->card->title,
					'items_count' => $checklist->items_count,
				])),
			'archive' => Inertia::optional(fn () => [
				'cards' => ArchivedCardResource::collection(
					$board->cards()->whereNotNull('cards.archived_at')->with('stack:id,name')->latest('cards.archived_at')->get(),
				),
				'stacks' => ArchivedStackResource::collection(
					$board->stacks()->whereNotNull('archived_at')->withCount('cards')->reorder()->latest('archived_at')->get(),
				),
			]),
		]);
	}

	/**
	 * Rename a board or change its description.
	 */
	public function update(BoardRequest $request, Board $board, UpdateBoard $updateBoard): RedirectResponse
	{
		Gate::authorize('update', $board);

		$updateBoard->handle($board, $request->validated());

		$this->toast(__('Board updated.'));

		return back();
	}

	/**
	 * Delete a board with all of its lists and cards.
	 */
	public function destroy(Board $board, DeleteBoard $deleteBoard): RedirectResponse
	{
		Gate::authorize('delete', $board);

		$deleteBoard->handle($board);

		$this->toast(__('Board deleted.'));

		return to_route('boards.index');
	}
}
