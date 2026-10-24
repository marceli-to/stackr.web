<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\User;
use App\Notifications\CardUpdated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotifyWatchers
{
	/**
	 * Tells everyone following the card what happened: its watchers, its
	 * members and the board's watchers, as long as they're still on the
	 * board. Never the person who did it, nor anyone in $except (they got
	 * a more specific notification).
	 *
	 * @param  array<int, int>  $except
	 */
	public function handle(Card $card, string $what, array $except = []): void
	{
		$board = $card->stack->board;
		$actor = Auth::user();
		$actor = $actor instanceof User ? $actor : null;

		$ids = $card->watchers()->pluck('users.id')
			->merge($card->members()->pluck('users.id'))
			->merge($board->watchers()->pluck('users.id'))
			->unique()
			->reject(fn (int $id): bool => $id === $actor?->id || in_array($id, $except, true));

		if ($ids->isEmpty()) {
			return;
		}

		$recipients = $board->members()->whereIn('users.id', $ids)->get();

		Notification::send($recipients, new CardUpdated($card, $actor, $what));
	}
}
