<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class MoveCard
{
	public function __construct(
		private CarryCardOver $carryCardOver,
		private MoveCardFiles $moveCardFiles,
		private RecordActivity $recordActivity,
	) {}

	/**
	 * Places the card at the given index among the target list's other cards
	 * (the ones on the board; archived cards keep their places). The list
	 * may be on another board; the card's files move with it.
	 */
	public function handle(Card $card, Stack $target, int $index): void
	{
		$source = $card->stack;
		$from = $source->board_id;

		DB::transaction(function () use ($card, $target, $index, $source, $from): void {
			$siblings = $target->cards()
				->active()
				->whereKeyNot($card->id)
				->get(['id', 'position'])
				->map(fn (Card $sibling): string => $sibling->position)
				->all();

			$card->stack()->associate($target);
			$card->position = FractionalIndex::forIndex($siblings, $index);
			$card->save();

			if ($target->board_id !== $from) {
				$this->carryCardOver->handle($card, $target->board);
				$this->moveCardFiles->handle($card, $from, $target->board_id);
				$this->recordActivity->handle($card, 'moved_board', [
					'from' => $source->board->name,
					'to' => $target->board->name,
				]);
			} elseif ($target->id !== $source->id) {
				$this->recordActivity->handle($card, 'moved', ['from' => $source->name, 'to' => $target->name]);
			}
		});
	}
}
