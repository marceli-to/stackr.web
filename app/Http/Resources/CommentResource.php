<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Support\Markdown;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'body' => $this->body,
			'body_html' => Markdown::toHtml($this->body),
			'user' => $this->user?->only('id', 'name'),
			'mine' => $this->user_id === $request->user()?->id,
			'created_at' => $this->created_at,
			'edited' => $this->updated_at?->gt($this->created_at->addSecond()) ?? false,
		];
	}
}
