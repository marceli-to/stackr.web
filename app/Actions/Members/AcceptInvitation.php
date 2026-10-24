<?php

namespace App\Actions\Members;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Support\Str;

class AcceptInvitation
{
	/**
	 * Adds the user to the invitation's project and uses up the invitation.
	 */
	public function handle(ProjectInvitation $invitation, User $user): Project
	{
		$project = $invitation->project;

		if ($project->roleOf($user) === null) {
			$project->members()->attach($user, ['role' => ProjectRole::Member->value]);
		}

		// The link arrived at this address, which proves the user owns it.
		if (Str::lower($user->email) === $invitation->email && ! $user->hasVerifiedEmail()) {
			$user->markEmailAsVerified();
		}

		$invitation->delete();

		return $project;
	}
}
