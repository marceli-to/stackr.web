<?php

namespace App\Actions\Stacks;

use App\Models\Stack;

class ArchiveStack
{
	/**
	 * Takes the list and its cards off the board; they keep their places.
	 */
	public function handle(Stack $stack): void
	{
		$stack->update(['archived_at' => now()]);
	}
}
