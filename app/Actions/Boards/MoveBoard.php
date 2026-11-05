<?php

namespace App\Actions\Boards;

use App\Models\BoardMembership;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class MoveBoard
{
	/**
	 * Places the board at the given index on its member's overview. Boards
	 * that were never placed get keys first, in the order they're shown, so
	 * the rest of the overview stays where it was.
	 */
	public function handle(BoardMembership $membership, int $index): void
	{
		DB::transaction(function () use ($membership, $index): void {
			$siblings = BoardMembership::inOverviewOrder(
				BoardMembership::query()
					->where('user_id', $membership->user_id)
					->whereKeyNot($membership->id)
					->with('board:id,name')
					->get(),
			);

			$key = null;

			foreach ($siblings as $sibling) {
				if ($sibling->position === null) {
					$sibling->update(['position' => FractionalIndex::between($key, null)]);
				}

				$key = $sibling->position;
			}

			$membership->update([
				'position' => FractionalIndex::forIndex($siblings->map(fn (BoardMembership $sibling): string => (string) $sibling->position)->all(), $index),
			]);
		});
	}
}
