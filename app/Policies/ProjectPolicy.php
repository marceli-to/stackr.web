<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    /**
     * Members can open a project's board.
     */
    public function view(User $user, Project $project): bool
    {
        return $project->roleOf($user) !== null;
    }

    /**
     * Members can rename a project and edit its description.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->roleOf($user) !== null;
    }

    /**
     * Only the owner can delete a project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $project->roleOf($user) === ProjectRole::Owner;
    }
}
