<?php

namespace App\Actions\Stacks;

use App\Actions\Cards\RecordActivity;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class MoveStackCards
{
	public function __construct(private RecordActivity $recordActivity) {}

	/**
	 * Moves the list's cards (the ones on the board, in order) to the bottom
	 * of another list on the same board. Each card logs the move, but
	 * nobody is notified, so a full list doesn't flood the bell.
	 *
	 * @return int how many cards moved
	 */
	public function handle(Stack $stack, Stack $target): int
	{
		return DB::transaction(function () use ($stack, $target): int {
			$key = $target->cards()->active()->reorder()->max('position');
			$cards = $stack->cards()->active()->get();

			foreach ($cards as $card) {
				$key = FractionalIndex::between($key, null);
				$card->stack()->associate($target);
				$card->position = $key;
				$card->save();

				$this->recordActivity->handle($card, 'moved', ['from' => $stack->name, 'to' => $target->name], notify: false);
			}

			return $cards->count();
		});
	}
}
