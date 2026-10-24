<?php

namespace App\Support;

use App\Models\Activity;

/**
 * An activity as one line for a notification, e.g. "Moved from To do to
 * Doing". (The card dialog writes its own, in the browser's language and
 * time zone: describeActivity() in card/board.ts.)
 */
class ActivityText
{
	public static function for(Activity $activity): string
	{
		$data = $activity->data ?? [];
		$value = fn (string $key): string => is_scalar($data[$key] ?? null) ? (string) $data[$key] : '';

		return match ($activity->type) {
			'created' => "Added to {$value('stack')}",
			'copied' => "Copied from “{$value('from')}”",
			'from_template' => "Created from the template “{$value('from')}”",
			'moved' => "Moved from {$value('from')} to {$value('to')}",
			'moved_board' => "Moved from the board {$value('from')} to {$value('to')}",
			'archived' => 'Archived',
			'restored' => 'Restored',
			'completed' => 'Marked complete',
			'reopened' => 'Marked incomplete',
			'due_set' => 'Changed the due date',
			'due_removed' => 'Removed the due date',
			'member_added' => "Added {$value('name')}",
			'member_removed' => "Removed {$value('name')}",
			'attached' => "Attached {$value('name')}",
			'checklist_added' => "Added the checklist {$value('title')}",
			'item_completed' => "Completed {$value('title')}",
			default => 'Changed the card',
		};
	}
}
