<?php

namespace App\Actions\Checklists;

use App\Models\Checklist;
use App\Models\ChecklistItem;

class CreateChecklistItem
{
	/**
	 * Adds an item to the end of the checklist.
	 */
	public function handle(Checklist $checklist, string $title): ChecklistItem
	{
		return $checklist->items()->create(['title' => $title]);
	}
}
