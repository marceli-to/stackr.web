<?php

namespace App\Actions\Cards;

use App\Models\Board;
use App\Models\Card;
use App\Models\User;

class SetWatching
{
	/**
	 * Starts or stops the user watching a card or a whole board.
	 */
	public function handle(Card|Board $watched, User $user, bool $watching): void
	{
		if ($watching) {
			$watched->watchers()->syncWithoutDetaching([$user->id]);
		} else {
			$watched->watchers()->detach($user->id);
		}
	}
}
