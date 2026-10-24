<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Label;
use App\Models\Project;

class CarryCardOver
{
	/**
	 * Fits a card that just arrived from another project to its new one:
	 * labels are matched by name and colour (and created when missing), and
	 * only members of the new project stay on it.
	 */
	public function handle(Card $card, Project $project): void
	{
		$labels = $card->labels()->get()->map(
			fn (Label $label): int => $project->labels()->firstOrCreate([
				'name' => $label->name,
				'color' => $label->color,
			])->id,
		);
		$card->labels()->sync($labels);

		$members = $project->members()->pluck('users.id');
		$card->members()->sync($card->members()->pluck('users.id')->intersect($members));
	}
}
