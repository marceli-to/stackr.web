<?php

namespace App\Actions\Members;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

class JoinByLink
{
	/**
	 * Adds the user to the project as a member, unless they're on it already.
	 */
	public function handle(Project $project, User $user): void
	{
		if ($project->roleOf($user) === null) {
			$project->members()->attach($user, ['role' => ProjectRole::Member->value]);
		}
	}
}
