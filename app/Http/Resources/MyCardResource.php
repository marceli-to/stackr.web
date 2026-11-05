<?php

namespace App\Http\Resources;

use App\Models\Card;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A card on "My cards", with its board and list. Expects stack.board,
 * labels and checklists.items loaded.
 *
 * @mixin Card
 */
class MyCardResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		$items = $this->checklists->flatMap(fn (Checklist $checklist) => $checklist->items);

		return [
			'id' => $this->id,
			'title' => $this->title,
			'start_at' => $this->start_at,
			'due_at' => $this->due_at,
			'completed_at' => $this->completed_at,
			'board' => [
				'key' => $this->stack->board->key,
				'name' => $this->stack->board->name,
				'color' => $this->stack->board->color,
			],
			'list' => $this->stack->name,
			'labels' => LabelResource::collection($this->labels),
			'checklist' => $items->isEmpty() ? null : [
				'done' => $items->whereNotNull('completed_at')->count(),
				'total' => $items->count(),
			],
		];
	}
}
