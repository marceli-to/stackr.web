<?php

namespace App\Actions\Comments;

use App\Models\Comment;

class UpdateComment
{
	public function handle(Comment $comment, string $body): void
	{
		$comment->update(['body' => $body]);
	}
}
