<?php

namespace App\Actions\Checklists;

use App\Models\Checklist;

class DeleteChecklist
{
	/**
	 * Deletes the checklist and its items.
	 */
	public function handle(Checklist $checklist): void
	{
		$checklist->delete();
	}
}
