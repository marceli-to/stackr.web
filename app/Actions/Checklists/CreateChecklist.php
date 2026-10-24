<?php

namespace App\Actions\Checklists;

use App\Actions\Cards\RecordActivity;
use App\Models\Card;
use App\Models\Checklist;

class CreateChecklist
{
	public function __construct(private RecordActivity $recordActivity) {}

	/**
	 * Adds a checklist, with the items of $copyFrom (unticked) if given.
	 */
	public function handle(Card $card, string $title, ?Checklist $copyFrom = null): Checklist
	{
		$checklist = $card->checklists()->create(['title' => $title]);

		foreach ($copyFrom->items ?? [] as $item) {
			$checklist->items()->create(['title' => $item->title]);
		}

		$this->recordActivity->handle($card, 'checklist_added', ['title' => $title]);

		return $checklist;
	}
}
