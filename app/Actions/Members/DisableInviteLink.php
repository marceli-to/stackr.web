<?php

namespace App\Actions\Members;

use App\Models\Board;

class DisableInviteLink
{
	public function handle(Board $board): void
	{
		$board->forceFill(['invite_token' => null])->save();
	}
}
