<?php

namespace App\Actions\Labels;

use App\Models\Label;
use App\Models\Project;

class CreateLabel
{
	/**
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Project $project, array $data): Label
	{
		return $project->labels()->create($data);
	}
}
