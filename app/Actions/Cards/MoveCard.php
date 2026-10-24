<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class MoveCard
{
	/**
	 * Places the card at the given index among the target list's other cards
	 * (the ones on the board; archived cards keep their places).
	 */
	public function handle(Card $card, Stack $target, int $index): void
	{
		DB::transaction(function () use ($card, $target, $index): void {
			$siblings = $target->cards()
				->active()
				->whereKeyNot($card->id)
				->get(['id', 'position'])
				->map(fn (Card $sibling): string => $sibling->position)
				->all();

			$card->stack()->associate($target);
			$card->position = FractionalIndex::forIndex($siblings, $index);
			$card->save();
		});
	}
}
