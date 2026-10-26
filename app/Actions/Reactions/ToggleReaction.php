<?php

namespace App\Actions\Reactions;

use App\Models\Comment;
use App\Models\User;

class ToggleReaction
{
	/**
	 * Adds the person's reaction, or takes it back if they had already
	 * reacted with that emoji.
	 */
	public function handle(Comment $comment, User $user, string $emoji): void
	{
		$existing = $comment->reactions()->where('user_id', $user->id)->where('emoji', $emoji)->first();

		if ($existing !== null) {
			$existing->delete();

			return;
		}

		$reaction = $comment->reactions()->make(['emoji' => $emoji]);
		$reaction->user()->associate($user);
		$reaction->save();
	}
}
