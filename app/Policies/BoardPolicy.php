<?php

namespace App\Policies;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
	/**
	 * Members can open a board.
	 */
	public function view(User $user, Board $board): bool
	{
		return $board->roleOf($user) !== null;
	}

	/**
	 * Members can rename a board and edit its description.
	 */
	public function update(User $user, Board $board): bool
	{
		return $board->roleOf($user) !== null;
	}

	/**
	 * Only the owner can delete a board.
	 */
	public function delete(User $user, Board $board): bool
	{
		return $board->roleOf($user) === BoardRole::Owner;
	}

	/**
	 * Only the owner invites people and removes members.
	 */
	public function manageMembers(User $user, Board $board): bool
	{
		return $board->roleOf($user) === BoardRole::Owner;
	}
}
