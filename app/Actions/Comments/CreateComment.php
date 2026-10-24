<?php

namespace App\Actions\Comments;

use App\Models\Card;
use App\Models\Comment;
use App\Models\User;

class CreateComment
{
	public function handle(Card $card, User $author, string $body): Comment
	{
		$comment = new Comment(['body' => $body]);
		$comment->card()->associate($card);
		$comment->user()->associate($author);
		$comment->save();

		return $comment;
	}
}
