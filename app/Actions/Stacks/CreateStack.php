<?php

namespace App\Actions\Stacks;

use App\Models\Project;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class CreateStack
{
	/**
	 * Adds a list to the end of the board.
	 */
	public function handle(Project $project, string $name): Stack
	{
		return DB::transaction(function () use ($project, $name): Stack {
			$last = $project->stacks()->reorder()->max('position');

			return $project->stacks()->create([
				'name' => $name,
				'position' => FractionalIndex::between($last, null),
			]);
		});
	}
}
