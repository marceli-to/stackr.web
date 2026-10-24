<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
	/**
	 * Mark a notification read and open its card, if it's still there for the user.
	 */
	public function open(Request $request, string $notification): RedirectResponse
	{
		/** @var User $user */
		$user = $request->user();

		$entry = $user->notifications()->findOrFail($notification);
		$entry->markAsRead();

		$cardId = $entry->data['card_id'] ?? null;
		$card = is_int($cardId) ? Card::query()->with('stack.board')->find($cardId) : null;

		if ($card === null || $user->cannot('view', $card->stack->board)) {
			$this->toast(__('That card is gone.'), 'error');

			return back();
		}

		return to_route('boards.show', [$card->stack->board, 'card' => $card->id]);
	}

	/**
	 * Mark every notification read.
	 */
	public function readAll(Request $request): RedirectResponse
	{
		/** @var User $user */
		$user = $request->user();

		$user->unreadNotifications()->update(['read_at' => now()]);

		return back();
	}
}
