<?php

namespace App\Actions\Comments;

use App\Models\Card;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\MentionedInComment;
use App\Support\Mentions;
use Illuminate\Support\Facades\Notification;

class CreateComment
{
	/**
	 * Saves the comment and lets the members it mentions know.
	 */
	public function handle(Card $card, User $author, string $body): Comment
	{
		$comment = new Comment(['body' => $body]);
		$comment->card()->associate($card);
		$comment->user()->associate($author);
		$comment->save();

		$mentioned = Mentions::in($body, $card->stack->project->members()->get())
			->reject(fn (User $member): bool => $member->is($author));

		Notification::send($mentioned, new MentionedInComment($comment, $author));

		return $comment;
	}
}
