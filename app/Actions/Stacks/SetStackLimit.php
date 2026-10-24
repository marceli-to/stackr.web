<?php

namespace App\Actions\Stacks;

use App\Models\Stack;

class SetStackLimit
{
	/**
	 * Sets how many cards the list should hold at most; null for no limit.
	 */
	public function handle(Stack $stack, ?int $limit): void
	{
		$stack->update(['wip_limit' => $limit]);
	}
}
