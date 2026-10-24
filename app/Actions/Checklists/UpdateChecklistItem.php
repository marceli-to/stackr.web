<?php

namespace App\Actions\Checklists;

use App\Actions\Cards\RecordActivity;
use App\Models\ChecklistItem;

class UpdateChecklistItem
{
	public function __construct(private RecordActivity $recordActivity) {}

	/**
	 * Renames the item, or ticks it off and back on. An item that is already
	 * done keeps the time it was ticked off.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function handle(ChecklistItem $item, array $data): void
	{
		if (array_key_exists('title', $data)) {
			$item->title = $data['title'];
		}

		if (array_key_exists('done', $data)) {
			$item->completed_at = $data['done'] ? ($item->completed_at ?? now()) : null;
		}

		$item->save();

		if ($item->wasChanged('completed_at') && $item->completed_at !== null) {
			$this->recordActivity->handle($item->checklist->card, 'item_completed', ['title' => $item->title]);
		}
	}
}
