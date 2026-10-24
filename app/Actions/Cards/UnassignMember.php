<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\User;

class UnassignMember
{
	public function handle(Card $card, User $user): void
	{
		$card->members()->detach($user->id);
	}
}
