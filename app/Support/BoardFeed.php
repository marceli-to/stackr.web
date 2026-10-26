<?php

namespace App\Support;

use App\Models\Activity;
use App\Models\Board;
use App\Models\Comment;
use Illuminate\Support\Collection;

/**
 * What happened on a board's cards, newest first: their activity and their
 * comments in one list. Archived cards are included, so the feed still says
 * who archived what.
 */
class BoardFeed
{
	/**
	 * The newest $limit entries, and whether there are older ones.
	 *
	 * @return array{entries: Collection<int, Activity|Comment>, more: bool}
	 */
	public static function latest(Board $board, int $limit): array
	{
		$cards = $board->cards()->select('cards.id');
		$with = ['user:id,name', 'card:id,title,stack_id,archived_at', 'card.stack:id,archived_at'];

		$activities = Activity::query()->whereIn('card_id', $cards)->with($with)
			->latest('id')->limit($limit + 1)->get();
		$comments = Comment::query()->whereIn('card_id', $cards)->with($with)
			->reorder()->latest('id')->limit($limit + 1)->get();

		/** @var Collection<int, Activity|Comment> $entries */
		$entries = collect([...$activities, ...$comments])
			->sortByDesc(fn (Activity|Comment $entry): int => $entry->created_at?->getTimestamp() ?? 0)
			->values();

		return [
			'entries' => $entries->take($limit),
			'more' => $entries->count() > $limit,
		];
	}
}
