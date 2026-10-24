<?php

namespace App\Actions\Stacks;

use App\Models\Stack;

class DeleteStack
{
	/**
	 * Deletes the list and its cards.
	 */
	public function handle(Stack $stack): void
	{
		$stack->delete();
	}
}
