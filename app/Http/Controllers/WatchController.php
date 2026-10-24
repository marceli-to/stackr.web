<?php

namespace App\Http\Controllers;

use App\Actions\Cards\SetWatching;
use App\Models\Board;
use App\Models\Card;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WatchController extends Controller
{
	/**
	 * Watch a card, or stop (DELETE).
	 */
	public function card(Request $request, Card $card, SetWatching $setWatching): RedirectResponse
	{
		Gate::authorize('view', $card->stack->board);

		/** @var User $user */
		$user = $request->user();

		$setWatching->handle($card, $user, $request->isMethod('post'));

		return back();
	}

	/**
	 * Watch a whole board, or stop (DELETE).
	 */
	public function board(Request $request, Board $board, SetWatching $setWatching): RedirectResponse
	{
		Gate::authorize('view', $board);

		/** @var User $user */
		$user = $request->user();

		$watching = $request->isMethod('post');
		$setWatching->handle($board, $user, $watching);

		$this->toast($watching
			? __('You’ll hear about changes on this board.')
			: __('You stopped watching this board.'));

		return back();
	}
}
