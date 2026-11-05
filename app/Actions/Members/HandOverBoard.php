<?php

namespace App\Actions\Members;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class HandOverBoard
{
	/**
	 * Makes a member the board's owner; the old owner stays on as a member.
	 * A board has exactly one owner throughout.
	 */
	public function handle(Board $board, User $owner, User $member): void
	{
		DB::transaction(function () use ($board, $owner, $member): void {
			$board->members()->updateExistingPivot($owner->id, ['role' => BoardRole::Member->value]);
			$board->members()->updateExistingPivot($member->id, ['role' => BoardRole::Owner->value]);
		});
	}
}
