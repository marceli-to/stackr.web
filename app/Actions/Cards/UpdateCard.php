<?php

namespace App\Actions\Cards;

use App\Models\Card;

class UpdateCard
{
	/**
	 * Saves only the fields that were sent. `completed` is a flag; a card that
	 * is already done keeps the time it was completed.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Card $card, array $data): void
	{
		if (array_key_exists('completed', $data)) {
			$data['completed_at'] = $data['completed'] ? ($card->completed_at ?? now()) : null;
			unset($data['completed']);
		}

		$card->update($data);
	}
}
