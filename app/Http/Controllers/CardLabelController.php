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
	 * Put one of the project's labels on a card.
	 */
	public function store(Card $card, Label $label, AddLabel $addLabel): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);
		abort_unless($label->project_id === $project->id, 404);

		$addLabel->handle($card, $label);

		return back();
	}

	/**
	 * Take a label off a card.
	 */
	public function destroy(Card $card, Label $label, RemoveLabel $removeLabel): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$removeLabel->handle($card, $label);

		return back();
	}
}
