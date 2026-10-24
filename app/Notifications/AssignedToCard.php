<?php

namespace App\Notifications;

class AssignedToCard extends CardNotification
{
	protected function sentence(): string
	{
		return __('added you to');
	}

	protected function subject(): string
	{
		return __(':name added you to “:card”', ['name' => $this->actor->name ?? '', 'card' => $this->card->title]);
	}
}
