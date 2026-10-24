<?php

namespace App\Actions\Members;

use App\Models\ProjectInvitation;

class WithdrawInvitation
{
	public function handle(ProjectInvitation $invitation): void
	{
		$invitation->delete();
	}
}
