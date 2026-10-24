<?php

namespace App\Actions\Cards;

use App\Models\Card;

class DeleteCard
{
	/**
	 * Deletes the card with everything on it.
	 */
	public function handle(Card $card): void
	{
		$card->delete();
	}
}
