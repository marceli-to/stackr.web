<?php

namespace App\Notifications;

use App\Models\Card;
use App\Models\User;

/**
 * Something happened on a card the person watches (or is a member of).
 * Only in the bell: moves and ticks would flood an inbox.
 */
class CardUpdated extends CardNotification
{
	public function __construct(Card $card, ?User $actor, public string $what)
	{
		parent::__construct($card, $actor);
	}

	public function via(User $notifiable): array
	{
		return ['database'];
	}

	protected function sentence(): string
	{
		return __('updated');
	}

	protected function subject(): string
	{
		return __('“:card” was updated', ['card' => $this->card->title]);
	}

	protected function excerpt(): string
	{
		return $this->what;
	}
}
