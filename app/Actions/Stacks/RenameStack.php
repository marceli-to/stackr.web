<?php

namespace App\Actions\Stacks;

use App\Models\Stack;

class RenameStack
{
	public function handle(Stack $stack, string $name): void
	{
		$stack->update(['name' => $name]);
	}
}
