<?php

namespace App\Actions\Members;

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemoveMember
{
	/**
	 * Takes the user off the board, off its cards and their watch lists.
	 */
	public function handle(Board $board, User $user): void
	{
		DB::transaction(function () use ($board, $user): void {
			$cards = $board->cards()->pluck('cards.id');

			DB::table('card_user')->where('user_id', $user->id)->whereIn('card_id', $cards)->delete();
			DB::table('card_watchers')->where('user_id', $user->id)->whereIn('card_id', $cards)->delete();
			$board->watchers()->detach($user);
			$board->members()->detach($user);
		});
	}
}
