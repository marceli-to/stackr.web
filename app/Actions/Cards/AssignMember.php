<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\User;
use App\Notifications\AssignedToCard;
use Illuminate\Support\Facades\Auth;

class AssignMember
{
	public function __construct(private RecordActivity $recordActivity) {}

	public function handle(Card $card, User $user): void
	{
		$attached = $card->members()->syncWithoutDetaching([$user->id])['attached'];

		if ($attached === []) {
			return;
		}

		$this->recordActivity->handle($card, 'member_added', ['user_id' => $user->id, 'name' => $user->name]);

		// Adding yourself needs no notification.
		$actor = Auth::user();

		if ($actor instanceof User && ! $actor->is($user)) {
			$user->notify(new AssignedToCard($card, $actor));
		}
	}
}
