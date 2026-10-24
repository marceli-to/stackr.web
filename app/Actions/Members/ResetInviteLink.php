<?php

namespace App\Actions\Members;

use App\Models\Board;
use Illuminate\Support\Str;

class ResetInviteLink
{
	/**
	 * Turns the board's join link on, or swaps it for a new one so the old
	 * link stops working.
	 */
	public function handle(Board $board): void
	{
		$board->forceFill(['invite_token' => Str::random(40)])->save();
	}
}
