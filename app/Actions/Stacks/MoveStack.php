<?php

namespace App\Actions\Stacks;

use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class MoveStack
{
	/**
	 * Places the list at the given index among the board's other lists.
	 */
	public function handle(Stack $stack, int $index): void
	{
		DB::transaction(function () use ($stack, $index): void {
			$siblings = $stack->project->stacks()
				->whereKeyNot($stack->id)
				->get(['id', 'position'])
				->map(fn (Stack $sibling): string => $sibling->position)
				->all();

			$stack->update(['position' => FractionalIndex::forIndex($siblings, $index)]);
		});
	}
}
