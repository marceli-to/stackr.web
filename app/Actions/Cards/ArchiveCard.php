<?php

namespace App\Actions\Cards;

use App\Models\Card;

class ArchiveCard
{
	/**
	 * Takes the card off the board; it keeps its place for a restore.
	 */
	public function handle(Card $card): void
	{
		$card->update(['archived_at' => now()]);
	}
}
