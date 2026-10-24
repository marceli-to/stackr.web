<?php

namespace App\Actions\Stacks;

use App\Models\Stack;

class RestoreStack
{
	/**
	 * Puts the list back where it was, with the cards it had.
	 */
	public function handle(Stack $stack): void
	{
		$stack->update(['archived_at' => null]);
	}
}
