<?php

namespace App\Http\Resources;

use App\Models\Card;
use App\Models\Checklist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * What a card's face on the board needs. Expects labels, members, cover,
 * checklists.items and the attachments and comments counts loaded.
 *
 * @mixin Card
 */
class CardFaceResource extends JsonResource
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
			'has_description' => filled($this->description),
			'start_at' => $this->start_at,
			'due_at' => $this->due_at,
			'completed_at' => $this->completed_at,
			'labels' => $this->labels->pluck('id'),
			'members' => $this->members->pluck('id'),
			'cover' => $this->cover !== null ? [
				'url' => route('attachments.thumbnail', $this->cover),
				'width' => $this->cover->width,
				'height' => $this->cover->height,
			] : null,
			'cover_color' => $this->cover_color,
			'attachments_count' => $this->attachments_count ?? 0,
			'comments_count' => $this->comments_count ?? 0,
			'checklist' => $items->isEmpty() ? null : [
				'done' => $items->whereNotNull('completed_at')->count(),
				'total' => $items->count(),
			],
		];
	}
}
