<?php

namespace App\Actions\Checklists;

use App\Actions\Cards\RecordActivity;
use App\Models\Card;
use App\Models\Checklist;

class CreateChecklist
{
	public function __construct(private RecordActivity $recordActivity) {}

	public function handle(Card $card, string $title): Checklist
	{
		$checklist = $card->checklists()->create(['title' => $title]);
		$this->recordActivity->handle($card, 'checklist_added', ['title' => $title]);

		return $checklist;
	}
}
