<?php

namespace App\Actions\Cards;

use App\Models\Activity;
use App\Models\Card;
use Illuminate\Support\Facades\Auth;

class RecordActivity
{
	/**
	 * Logs what just happened to the card, by whoever is signed in.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Card $card, string $type, array $data = []): Activity
	{
		$activity = new Activity(['type' => $type, 'data' => $data ?: null]);
		$activity->card()->associate($card);
		$activity->user_id = Auth::id();
		$activity->save();

		return $activity;
	}
}
