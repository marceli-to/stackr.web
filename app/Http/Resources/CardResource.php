<?php

namespace App\Http\Resources;

use App\Models\Card;
use App\Support\Markdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything the card dialog shows.
 *
 * @mixin Card
 */
class CardResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		// The board loads only what card faces need (e.g. checklist items
		// without their titles), so the open card loads its own copies.
		$this->resource->load(['checklists.items', 'attachments.user', 'comments.user']);
		$this->resource->loadMissing(['stack', 'labels', 'members']);

		return [
			'id' => $this->id,
			'title' => $this->title,
			'description' => $this->description,
			'description_html' => Markdown::toHtml($this->description),
			'start_at' => $this->start_at,
			'due_at' => $this->due_at,
			'completed_at' => $this->completed_at,
			'created_at' => $this->created_at,
			'stack' => $this->stack->only('id', 'name'),
			'labels' => $this->labels->pluck('id'),
			'members' => $this->members->pluck('id'),
			'cover_attachment_id' => $this->cover_attachment_id,
			'checklists' => ChecklistResource::collection($this->checklists),
			'attachments' => AttachmentResource::collection($this->attachments),
			'comments' => CommentResource::collection($this->comments),
		];
	}
}
