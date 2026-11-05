<?php

namespace App\Http\Controllers;

use App\Actions\Stacks\ArchiveStack;
use App\Actions\Stacks\ArchiveStackCards;
use App\Actions\Stacks\CopyStack;
use App\Actions\Stacks\CreateStack;
use App\Actions\Stacks\DeleteStack;
use App\Actions\Stacks\MoveStack;
use App\Actions\Stacks\MoveStackCards;
use App\Actions\Stacks\MoveStackToBoard;
use App\Actions\Stacks\RenameStack;
use App\Actions\Stacks\RestoreStack;
use App\Actions\Stacks\SetStackLimit;
use App\Actions\Stacks\SortStack;
use App\Enums\CardSort;
use App\Http\Requests\MoveRequest;
use App\Http\Requests\StackBoardMoveRequest;
use App\Http\Requests\StackCardsMoveRequest;
use App\Http\Requests\StackLimitRequest;
use App\Http\Requests\StackRequest;
use App\Http\Requests\StackSortRequest;
use App\Models\Board;
use App\Models\Stack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StackController extends Controller
{
	/**
	 * Add a list to the end of a board.
	 */
	public function store(StackRequest $request, Board $board, CreateStack $createStack): RedirectResponse
	{
		Gate::authorize('update', $board);

		$createStack->handle($board, $request->validated('name'));

		return back();
	}

	/**
	 * Rename a list.
	 */
	public function update(StackRequest $request, Stack $stack, RenameStack $renameStack): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$renameStack->handle($stack, $request->validated('name'));

		return back();
	}

	/**
	 * Set or clear a list's card limit.
	 */
	public function limit(StackLimitRequest $request, Stack $stack, SetStackLimit $setStackLimit): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$setStackLimit->handle($stack, $request->filled('wip_limit') ? $request->integer('wip_limit') : null);

		return back();
	}

	/**
	 * Move a list to a new place on the board.
	 */
	public function move(MoveRequest $request, Stack $stack, MoveStack $moveStack): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$moveStack->handle($stack, $request->integer('index'));

		return back();
	}

	/**
	 * Move a list with its cards to the end of another of the user's boards.
	 */
	public function moveToBoard(StackBoardMoveRequest $request, Stack $stack, MoveStackToBoard $moveStackToBoard): RedirectResponse
	{
		$target = Board::query()->findOrFail($request->integer('board_id'));

		$moveStackToBoard->handle($stack, $target);

		$this->toast(__(':list moved to :board.', ['list' => $stack->name, 'board' => $target->name]));

		return back();
	}

	/**
	 * Copy a list and its cards, right after the original.
	 */
	public function copy(StackRequest $request, Stack $stack, CopyStack $copyStack): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$copyStack->handle($stack, $request->validated('name'));

		$this->toast(__('List copied.'));

		return back();
	}

	/**
	 * Sort a list's cards once, by due date, creation date or title.
	 */
	public function sort(StackSortRequest $request, Stack $stack, SortStack $sortStack): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$sortStack->handle($stack, $request->enum('by', CardSort::class) ?? CardSort::Due);

		return back();
	}

	/**
	 * Move every card in a list to another list on the board.
	 */
	public function moveCards(StackCardsMoveRequest $request, Stack $stack, MoveStackCards $moveStackCards): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$target = Stack::query()->findOrFail($request->integer('stack_id'));
		$count = $moveStackCards->handle($stack, $target);

		$this->toast(trans_choice('{0} No cards to move.|{1} 1 card moved to :list.|[2,*] :count cards moved to :list.', $count, ['list' => $target->name]));

		return back();
	}

	/**
	 * Archive every card in a list; the list stays.
	 */
	public function archiveCards(Stack $stack, ArchiveStackCards $archiveStackCards): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$count = $archiveStackCards->handle($stack);

		$this->toast(trans_choice('{0} No cards to archive.|{1} 1 card archived.|[2,*] :count cards archived.', $count));

		return back();
	}

	/**
	 * Take a list and its cards off the board.
	 */
	public function archive(Stack $stack, ArchiveStack $archiveStack): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$archiveStack->handle($stack);

		$this->toast(__('List archived.'));

		return back();
	}

	/**
	 * Put an archived list back on the board.
	 */
	public function restore(Stack $stack, RestoreStack $restoreStack): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$restoreStack->handle($stack);

		$this->toast(__('List restored.'));

		return back();
	}

	/**
	 * Delete a list and its cards.
	 */
	public function destroy(Stack $stack, DeleteStack $deleteStack): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		$deleteStack->handle($stack);

		$this->toast(__('List deleted.'));

		return back();
	}
}
