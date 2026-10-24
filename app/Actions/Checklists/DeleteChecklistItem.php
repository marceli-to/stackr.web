<?php

namespace App\Actions\Checklists;

use App\Models\ChecklistItem;

class DeleteChecklistItem
{
	public function handle(ChecklistItem $item): void
	{
		$item->delete();
	}
}
