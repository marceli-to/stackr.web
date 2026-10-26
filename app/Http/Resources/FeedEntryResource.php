<?php

namespace App\Http\Resources;

use App\Models\Activity;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * An entry in the board's activity feed: something done to a card, or a
 * comment on it. The panel turns an activity's type and data into a
 * sentence, as the card dialog does.
 *
 * @property Activity|Comment $resource
 */
class FeedEntryResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		$entry = $this->resource;
		$card = $entry->card;
		$comment = $entry instanceof Comment;

		return [
			'key' => ($comment ? 'c' : 'a').$entry->id,
			'kind' => $comment ? 'comment' : 'activity',
			'type' => $comment ? null : $entry->type,
			'data' => (object) ($comment ? [] : $entry->data ?? []),
			'snippet' => $comment ? Str::limit(Str::squish($entry->body), 160) : null,
			'user' => $entry->user?->only('id', 'name'),
			'via_agent' => $entry->via_agent,
			'created_at' => $entry->created_at,
			'card' => [
				'id' => $card->id,
				'title' => $card->title,
				// Off the board: archived itself, or in an archived list.
				'archived' => $card->archived_at !== null || $card->stack->archived_at !== null,
			],
		];
	}
}
