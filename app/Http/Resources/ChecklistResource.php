<?php

namespace App\Http\Resources;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Checklist
 */
class ChecklistResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'items' => $this->items->map(fn (ChecklistItem $item) => [
				'id' => $item->id,
				'title' => $item->title,
				'done' => $item->completed_at !== null,
			]),
		];
	}
}
