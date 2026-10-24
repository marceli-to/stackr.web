<?php

namespace App\Actions\Members;

use App\Models\Project;
use Illuminate\Support\Str;

class ResetInviteLink
{
	/**
	 * Turns the project's join link on, or swaps it for a new one so the old
	 * link stops working.
	 */
	public function handle(Project $project): void
	{
		$project->forceFill(['invite_token' => Str::random(40)])->save();
	}
}
