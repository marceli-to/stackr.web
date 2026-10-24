<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class CreateCard
{
	public function __construct(private RecordActivity $recordActivity) {}

	/**
	 * Adds a card to the bottom of the list.
	 */
	public function handle(Stack $stack, string $title): Card
	{
		return DB::transaction(function () use ($stack, $title): Card {
			$last = $stack->cards()->reorder()->max('position');

			$card = $stack->cards()->create([
				'title' => $title,
				'position' => FractionalIndex::between($last, null),
			]);

			$this->recordActivity->handle($card, 'created', ['stack' => $stack->name]);

			return $card;
		});
	}
}
