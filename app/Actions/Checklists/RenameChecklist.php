<?php

namespace App\Actions\Checklists;

use App\Models\Checklist;

class RenameChecklist
{
	public function handle(Checklist $checklist, string $title): void
	{
		$checklist->update(['title' => $title]);
	}
}
