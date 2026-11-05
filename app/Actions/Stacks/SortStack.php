<?php

namespace App\Actions\Stacks;

use App\Enums\CardSort;
use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class SortStack
{
	/**
	 * Puts the list's cards in the given order once; later cards and drags
	 * work as usual. Cards that compare equal keep their current order.
	 * Archived cards are left where they are.
	 */
	public function handle(Stack $stack, CardSort $by): void
	{
		DB::transaction(function () use ($stack, $by): void {
			$cards = $stack->cards()->active()->get(['id', 'title', 'position', 'due_at', 'created_at']);

			$sorted = $cards->sort(fn (Card $a, Card $b): int => $this->compare($a, $b, $by)
				?: strcmp($a->position, $b->position))->values();

			$key = null;

			foreach ($sorted as $card) {
				$key = FractionalIndex::between($key, null);

				if ($card->position !== $key) {
					$card->update(['position' => $key]);
				}
			}
		});
	}

	private function compare(Card $a, Card $b, CardSort $by): int
	{
		return match ($by) {
			// Soonest first; cards without a due date go last.
			CardSort::Due => match (true) {
				$a->due_at === null && $b->due_at === null => 0,
				$a->due_at === null => 1,
				$b->due_at === null => -1,
				default => $a->due_at <=> $b->due_at,
			},
			CardSort::Newest => $b->created_at <=> $a->created_at ?: $b->id <=> $a->id,
			CardSort::Oldest => $a->created_at <=> $b->created_at ?: $a->id <=> $b->id,
			CardSort::Title => strnatcasecmp($a->title, $b->title),
		};
	}
}
