<?php

namespace App\Http\Controllers;

use App\Actions\Cards\AddLabel;
use App\Actions\Cards\RemoveLabel;
use App\Models\Card;
use App\Models\Label;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CardLabelController extends Controller
{
	/**
	 * Put one of the board's labels on a card.
	 */
	public function store(Card $card, Label $label, AddLabel $addLabel): RedirectResponse
	{
		$board = $card->stack->board;

		Gate::authorize('update', $board);
		abort_unless($label->board_id === $board->id, 404);

		$addLabel->handle($card, $label);

		return back();
	}

	/**
	 * Take a label off a card.
	 */
	public function destroy(Card $card, Label $label, RemoveLabel $removeLabel): RedirectResponse
	{
		Gate::authorize('update', $card->stack->board);

		$removeLabel->handle($card, $label);

		return back();
	}
}
