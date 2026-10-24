<?php

namespace App\Actions\Boards;

use App\Models\Board;

class DeleteBoard
{
	/**
	 * Deletes the board with all of its lists, cards and files.
	 */
	public function handle(Board $board): void
	{
		$board->delete();
	}
}
