<?php

namespace App\Actions\Stacks;

use App\Models\Board;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class CreateStack
{
	/**
	 * Adds a list to the end of the board.
	 */
	public function handle(Board $board, string $name): Stack
	{
		return DB::transaction(function () use ($board, $name): Stack {
			$last = $board->stacks()->reorder()->max('position');

			return $board->stacks()->create([
				'name' => $name,
				'position' => FractionalIndex::between($last, null),
			]);
		});
	}
}
