<?php

namespace App\Actions\Checklists;

use App\Models\Card;
use App\Models\Checklist;

class CreateChecklist
{
	public function handle(Card $card, string $title): Checklist
	{
		return $card->checklists()->create(['title' => $title]);
	}
}
