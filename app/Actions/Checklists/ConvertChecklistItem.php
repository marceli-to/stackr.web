<?php

namespace App\Actions\Checklists;

use App\Actions\Cards\RecordActivity;
use App\Models\Card;
use App\Models\ChecklistItem;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class ConvertChecklistItem
{
	public function __construct(private RecordActivity $recordActivity) {}

	/**
	 * Turns the item into a card of its own, right below its card in the
	 * same list, and takes it off the checklist. A ticked item makes a
	 * completed card.
	 */
	public function handle(ChecklistItem $item): Card
	{
		return DB::transaction(function () use ($item): Card {
			$source = $item->checklist->card;
			$next = $source->stack->cards()
				->active()
				->reorder()
				->where('position', '>', $source->position)
				->min('position');

			$card = $source->stack->cards()->create([
				'title' => $item->title,
				'completed_at' => $item->completed_at,
				'position' => FractionalIndex::between($source->position, is_string($next) ? $next : null),
			]);

			$item->delete();

			$this->recordActivity->handle($card, 'from_checklist', ['from' => $source->title]);

			return $card;
		});
	}
}
