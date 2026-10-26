<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Reaction;
use App\Support\Markdown;
use App\Support\Mentions;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
	/**
	 * The board members' names, to highlight their mentions.
	 *
	 * @var list<string>
	 */
	public array $names = [];

	/**
	 * @param  list<string>  $names
	 */
	public function mentioning(array $names): static
	{
		$this->names = $names;

		return $this;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'body' => $this->body,
			'body_html' => Mentions::highlight((string) Markdown::toHtml($this->body), $this->names),
			'user' => $this->user?->only('id', 'name'),
			'mine' => $this->user_id === $request->user()?->id,
			'via_agent' => $this->via_agent,
			'created_at' => $this->created_at,
			'edited' => $this->updated_at?->gt($this->created_at->addSecond()) ?? false,
			// One entry per emoji, in the order they were first used.
			'reactions' => $this->reactions
				->groupBy('emoji')
				->map(fn ($reactions, string $emoji): array => [
					'emoji' => $emoji,
					'count' => $reactions->count(),
					'mine' => $reactions->contains('user_id', $request->user()?->id),
					'names' => $reactions->map(fn (Reaction $reaction): ?string => $reaction->user?->name)->filter()->values(),
				])
				->values(),
		];
	}
}
