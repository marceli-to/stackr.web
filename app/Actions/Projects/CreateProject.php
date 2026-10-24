<?php

namespace App\Actions\Projects;

use App\Enums\ProjectColor;
use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProject
{
	/**
	 * Without a chosen colour, a new project takes the one after the owner's latest project.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function handle(User $owner, array $data): Project
	{
		return DB::transaction(function () use ($owner, $data): Project {
			$latest = $owner->projects()->latest('projects.id')->first();

			$project = Project::create([
				'color' => $latest?->color->next() ?? ProjectColor::Cobalt,
				...$data,
			]);
			$project->members()->attach($owner, ['role' => ProjectRole::Owner->value]);

			return $project;
		});
	}
}
