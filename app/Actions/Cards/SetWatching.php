<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Project;
use App\Models\User;

class SetWatching
{
	/**
	 * Starts or stops the user watching a card or a whole board.
	 */
	public function handle(Card|Project $watched, User $user, bool $watching): void
	{
		if ($watching) {
			$watched->watchers()->syncWithoutDetaching([$user->id]);
		} else {
			$watched->watchers()->detach($user->id);
		}
	}
}
