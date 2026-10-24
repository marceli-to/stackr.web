<?php

namespace App\Actions\Comments;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\MentionedInComment;
use App\Support\Mentions;
use Illuminate\Support\Facades\Notification;

class UpdateComment
{
	/**
	 * Saves the new text. Only people mentioned for the first time hear about it.
	 */
	public function handle(Comment $comment, string $body): void
	{
		$members = $comment->card->stack->board->members()->get();
		$before = Mentions::in($comment->body, $members)->pluck('id');

		$comment->update(['body' => $body]);

		$author = $comment->user;

		if ($author === null) {
			return;
		}

		$mentioned = Mentions::in($body, $members)
			->reject(fn (User $member): bool => $before->contains($member->id) || $member->is($author));

		Notification::send($mentioned, new MentionedInComment($comment, $author));
	}
}
