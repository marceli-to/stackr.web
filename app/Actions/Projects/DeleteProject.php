<?php

namespace App\Actions\Projects;

use App\Models\Project;

class DeleteProject
{
	/**
	 * Deletes the project with all of its lists, cards and files.
	 */
	public function handle(Project $project): void
	{
		$project->delete();
	}
}
