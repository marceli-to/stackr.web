<?php

namespace App\Actions\Stacks;

use App\Actions\Cards\CarryCardOver;
use App\Actions\Cards\MoveCardFiles;
use App\Actions\Cards\RecordActivity;
use App\Models\Board;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class MoveStackToBoard
{
	public function __construct(
		private CarryCardOver $carryCardOver,
		private MoveCardFiles $moveCardFiles,
		private RecordActivity $recordActivity,
	) {}

	/**
	 * Moves the list, with all of its cards (archived ones too), to the end
	 * of another board. Each card is fitted to the new board like a single
	 * card move: labels matched, outsiders taken off, files moved. The move
	 * is logged on each active card without notifying anyone.
	 */
	public function handle(Stack $stack, Board $target): void
	{
		$source = $stack->board;

		DB::transaction(function () use ($stack, $source, $target): void {
			$last = $target->stacks()->active()->reorder()->max('position');

			$stack->board()->associate($target);
			$stack->position = FractionalIndex::between(is_string($last) ? $last : null, null);
			$stack->save();

			foreach ($stack->cards()->get() as $card) {
				$card->setRelation('stack', $stack);
				$this->carryCardOver->handle($card, $target);
				$this->moveCardFiles->handle($card, $source->id, $target->id);

				if ($card->archived_at === null) {
					$this->recordActivity->handle($card, 'moved_board', [
						'from' => $source->name,
						'to' => $target->name,
					], notify: false);
				}
			}
		});
	}
}
