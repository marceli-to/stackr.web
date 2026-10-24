<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class CreateCard
{
	/**
	 * Adds a card to the bottom of the list.
	 */
	public function handle(Stack $stack, string $title): Card
	{
		return DB::transaction(function () use ($stack, $title): Card {
			$last = $stack->cards()->reorder()->max('position');

			return $stack->cards()->create([
				'title' => $title,
				'position' => FractionalIndex::between($last, null),
			]);
		});
	}
}
