<?php

namespace App\Actions\Cards;

use App\Models\Card;
use Illuminate\Support\Facades\DB;

class RestoreCard
{
	/**
	 * Puts the card back where it was. An archived list comes back with it,
	 * or the card would have nowhere to show.
	 */
	public function handle(Card $card): void
	{
		DB::transaction(function () use ($card): void {
			$card->update(['archived_at' => null]);
			$card->stack->update(['archived_at' => null]);
		});
	}
}
