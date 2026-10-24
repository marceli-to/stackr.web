<?php

namespace App\Actions\Cards;

use App\Models\Board;
use App\Models\Card;
use App\Models\Label;

class CarryCardOver
{
	/**
	 * Fits a card that just arrived from another board to its new one:
	 * labels are matched by name and colour (and created when missing), and
	 * only members of the new board stay on it.
	 */
	public function handle(Card $card, Board $board): void
	{
		$labels = $card->labels()->get()->map(
			fn (Label $label): int => $board->labels()->firstOrCreate([
				'name' => $label->name,
				'color' => $label->color,
			])->id,
		);
		$card->labels()->sync($labels);

		$members = $board->members()->pluck('users.id');
		$card->members()->sync($card->members()->pluck('users.id')->intersect($members));
	}
}
