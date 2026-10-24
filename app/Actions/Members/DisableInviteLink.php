<?php

namespace App\Actions\Members;

use App\Models\Project;

class DisableInviteLink
{
	public function handle(Project $project): void
	{
		$project->forceFill(['invite_token' => null])->save();
	}
}
