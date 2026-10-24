<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\User;

class AssignMember
{
	public function handle(Card $card, User $user): void
	{
		$card->members()->syncWithoutDetaching([$user->id]);
	}
}
