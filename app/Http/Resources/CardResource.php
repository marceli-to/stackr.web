<?php

namespace App\Http\Resources;

use App\Models\Card;
use App\Models\Comment;
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
		$this->resource->load([
			'checklists.items',
			'attachments.user',
			'comments.user',
			'activities' => fn ($query) => $query->with('user')->limit(100),
		]);
		$this->resource->loadMissing(['stack', 'labels', 'members']);

		/** @var list<string> $names */
		$names = $this->stack->board->members()->pluck('name')->all();

		return [
			'id' => $this->id,
			'title' => $this->title,
			'description' => $this->description,
			'description_html' => Markdown::toHtml($this->description),
			'start_at' => $this->start_at,
			'due_at' => $this->due_at,
			'completed_at' => $this->completed_at,
			// Off the board: archived itself, or in an archived list.
			'is_template' => $this->is_template,
			'watching' => $request->user() !== null && $this->watchers()->whereKey($request->user()->id)->exists(),
			'archived' => $this->archived_at !== null || $this->stack->archived_at !== null,
			'created_at' => $this->created_at,
			'stack' => $this->stack->only('id', 'name'),
			// Its place among the list's cards on the board; null when archived.
			'index' => $this->archived_at === null
				? $this->stack->cards()->active()->where('position', '<', $this->position)->count()
				: null,
			'labels' => $this->labels->pluck('id'),
			'members' => $this->members->pluck('id'),
			'cover_attachment_id' => $this->cover_attachment_id,
			'cover_color' => $this->cover_color,
			'checklists' => ChecklistResource::collection($this->checklists),
			'attachments' => AttachmentResource::collection($this->attachments),
			'comments' => $this->comments->map(fn (Comment $comment) => (new CommentResource($comment))->mentioning($names)),
			'activities' => ActivityResource::collection($this->activities),
		];
	}
}
