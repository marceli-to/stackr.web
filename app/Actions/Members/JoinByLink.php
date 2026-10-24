<?php

namespace App\Actions\Members;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;

class JoinByLink
{
	/**
	 * Adds the user to the board as a member, unless they're on it already.
	 */
	public function handle(Board $board, User $user): void
	{
		if ($board->roleOf($user) === null) {
			$board->members()->attach($user, ['role' => BoardRole::Member->value]);
		}
	}
}
