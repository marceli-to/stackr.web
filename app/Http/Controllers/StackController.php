<?php

namespace App\Http\Controllers;

use App\Actions\Stacks\ArchiveStack;
use App\Actions\Stacks\CreateStack;
use App\Actions\Stacks\DeleteStack;
use App\Actions\Stacks\MoveStack;
use App\Actions\Stacks\RenameStack;
use App\Actions\Stacks\RestoreStack;
use App\Actions\Stacks\SetStackLimit;
use App\Http\Requests\MoveRequest;
use App\Http\Requests\StackLimitRequest;
use App\Http\Requests\StackRequest;
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
