<?php

namespace App\Actions\Members;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\BoardInvitation;
use App\Models\User;
use Illuminate\Support\Str;

class AcceptInvitation
{
	/**
	 * Adds the user to the invitation's board and uses up the invitation.
	 */
	public function handle(BoardInvitation $invitation, User $user): Board
	{
		$board = $invitation->board;

		if ($board->roleOf($user) === null) {
			$board->members()->attach($user, ['role' => BoardRole::Member->value]);
		}

		// The link arrived at this address, which proves the user owns it.
		if (Str::lower($user->email) === $invitation->email && ! $user->hasVerifiedEmail()) {
			$user->markEmailAsVerified();
		}

		$invitation->delete();

		return $board;
	}
}
