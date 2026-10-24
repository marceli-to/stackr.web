<?php

namespace App\Actions\Cards;

use App\Models\Card;

class ArchiveCard
{
	public function __construct(private RecordActivity $recordActivity) {}

	/**
	 * Takes the card off the board; it keeps its place for a restore.
	 */
	public function handle(Card $card): void
	{
		$card->update(['archived_at' => now()]);
		$this->recordActivity->handle($card, 'archived');
	}
}
