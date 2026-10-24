<?php

namespace App\Actions\Labels;

use App\Models\Board;
use App\Models\Label;

class CreateLabel
{
	/**
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Board $board, array $data): Label
	{
		return $board->labels()->create($data);
	}
}
