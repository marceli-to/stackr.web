<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Str;

class MentionedInComment extends CardNotification
{
	public function __construct(public Comment $comment, User $actor)
	{
		parent::__construct($comment->card, $actor);
	}

	protected function sentence(): string
	{
		return __('mentioned you on');
	}

	protected function subject(): string
	{
		return __(':name mentioned you on “:card”', ['name' => $this->actor->name ?? '', 'card' => $this->card->title]);
	}

	protected function excerpt(): string
	{
		return Str::limit(Str::squish($this->comment->body), 160);
	}
}
