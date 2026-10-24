<?php

namespace App\Actions\Boards;

use App\Enums\BoardColor;
use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateBoard
{
	/**
	 * Without a chosen colour, a new board takes the one after the owner's latest board.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function handle(User $owner, array $data): Board
	{
		return DB::transaction(function () use ($owner, $data): Board {
			$latest = $owner->boards()->latest('boards.id')->first();

			$board = Board::create([
				'color' => $latest?->color->next() ?? BoardColor::Cobalt,
				...$data,
			]);
			$board->members()->attach($owner, ['role' => BoardRole::Owner->value]);

			return $board;
		});
	}
}
