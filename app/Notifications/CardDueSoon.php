<?php

namespace App\Notifications;

class CardDueSoon extends CardNotification
{
	protected function sentence(): string
	{
		return __('Due soon:');
	}

	protected function subject(): string
	{
		return __('“:card” is due soon', ['card' => $this->card->title]);
	}

	protected function excerpt(): ?string
	{
		return $this->card->due_at !== null
			? 'Due '.$this->card->due_at->diffForHumans()
			: null;
	}
}
