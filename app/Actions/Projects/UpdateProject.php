<?php

namespace App\Actions\Projects;

use App\Models\Project;

class UpdateProject
{
	/**
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Project $project, array $data): void
	{
		$project->update($data);
	}
}
