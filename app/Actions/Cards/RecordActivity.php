<?php

namespace App\Actions\Cards;

use App\Models\Activity;
use App\Models\Card;
use App\Support\ActivityText;
use App\Support\Agent;
use Illuminate\Support\Facades\Auth;

class RecordActivity
{
	public function __construct(private NotifyWatchers $notifyWatchers) {}

	/**
	 * Logs what just happened to the card, by whoever is signed in, and tells
	 * the people following it. Someone just added to the card hears about
	 * that from AssignedToCard instead.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Card $card, string $type, array $data = []): Activity
	{
		$activity = new Activity(['type' => $type, 'data' => $data ?: null, 'via_agent' => Agent::acting()]);
		$activity->card()->associate($card);
		$activity->user()->associate(Auth::user());
		$activity->save();

		$this->notifyWatchers->handle(
			$card,
			ActivityText::for($activity),
			$type === 'member_added' && is_int($data['user_id'] ?? null) ? [$data['user_id']] : [],
		);

		return $activity;
	}
}
