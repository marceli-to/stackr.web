<?php

namespace App\Actions\Comments;

use App\Actions\Cards\NotifyWatchers;
use App\Models\Card;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\MentionedInComment;
use App\Support\Agent;
use App\Support\Mentions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CreateComment
{
	public function __construct(private NotifyWatchers $notifyWatchers) {}

	/**
	 * Saves the comment and lets the members it mentions know; everyone
	 * else following the card gets a plainer update.
	 */
	public function handle(Card $card, User $author, string $body): Comment
	{
		$comment = new Comment(['body' => $body, 'via_agent' => Agent::acting()]);
		$comment->card()->associate($card);
		$comment->user()->associate($author);
		$comment->save();

		$mentioned = Mentions::in($body, $card->stack->project->members()->get())
			->reject(fn (User $member): bool => $member->is($author));

		Notification::send($mentioned, new MentionedInComment($comment, $author));

		$this->notifyWatchers->handle(
			$card,
			'Commented: '.Str::limit(Str::squish($body), 140),
			$mentioned->map(fn (User $member): int => $member->id)->values()->all(),
		);

		return $comment;
	}
}
