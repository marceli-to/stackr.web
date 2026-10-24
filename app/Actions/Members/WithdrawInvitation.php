<?php

namespace App\Actions\Members;

use App\Models\BoardInvitation;

class WithdrawInvitation
{
	public function handle(BoardInvitation $invitation): void
	{
		$invitation->delete();
	}
}
