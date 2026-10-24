<?php

namespace App\Actions\Checklists;

use App\Models\ChecklistItem;

class UpdateChecklistItem
{
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
	}
}
