<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\User;

class AssignMember
{
	public function __construct(private RecordActivity $recordActivity) {}

	public function handle(Card $card, User $user): void
	{
		$attached = $card->members()->syncWithoutDetaching([$user->id])['attached'];

		if ($attached !== []) {
			$this->recordActivity->handle($card, 'member_added', ['user_id' => $user->id, 'name' => $user->name]);
		}
	}
}
