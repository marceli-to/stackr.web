<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\User;

class UnassignMember
{
	public function __construct(private RecordActivity $recordActivity) {}

	public function handle(Card $card, User $user): void
	{
		if ($card->members()->detach($user->id) > 0) {
			$this->recordActivity->handle($card, 'member_removed', ['user_id' => $user->id, 'name' => $user->name]);
		}
	}
}
