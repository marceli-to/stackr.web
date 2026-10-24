<?php

namespace App\Actions\Stacks;

use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class MoveStack
{
	/**
	 * Places the list at the given index among the board's other lists
	 * (archived lists keep their places).
	 */
	public function handle(Stack $stack, int $index): void
	{
		DB::transaction(function () use ($stack, $index): void {
			$siblings = $stack->board->stacks()
				->active()
				->whereKeyNot($stack->id)
				->get(['id', 'position'])
				->map(fn (Stack $sibling): string => $sibling->position)
				->all();

			$stack->update(['position' => FractionalIndex::forIndex($siblings, $index)]);
		});
	}
}
