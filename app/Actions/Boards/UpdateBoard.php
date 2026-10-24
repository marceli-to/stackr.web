<?php

namespace App\Actions\Boards;

use App\Models\Board;

class UpdateBoard
{
	/**
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Board $board, array $data): void
	{
		$board->update($data);
	}
}
