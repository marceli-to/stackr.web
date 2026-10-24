<?php

namespace App\Actions\Cards;

use App\Models\Card;

class UpdateCard
{
	public function __construct(private RecordActivity $recordActivity) {}

	/**
	 * Saves only the fields that were sent. `completed` is a flag; a card that
	 * is already done keeps the time it was completed. Completion and the due
	 * date go into the activity log.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Card $card, array $data): void
	{
		if (array_key_exists('completed', $data)) {
			$data['completed_at'] = $data['completed'] ? ($card->completed_at ?? now()) : null;
			unset($data['completed']);
		}

		$card->fill($data);

		// A new due date gets its own reminder.
		if ($card->isDirty('due_at')) {
			$card->due_reminded_at = null;
		}

		$card->save();

		if ($card->wasChanged('completed_at')) {
			$this->recordActivity->handle($card, $card->completed_at !== null ? 'completed' : 'reopened');
		}

		if ($card->wasChanged('due_at')) {
			$this->recordActivity->handle($card, $card->due_at !== null ? 'due_set' : 'due_removed', array_filter([
				'due' => $card->due_at?->toIso8601String(),
			]));
		}
	}
}
