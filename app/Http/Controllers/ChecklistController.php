<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Checklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChecklistController extends Controller
{
	/**
	 * Add a checklist to a card.
	 */
	public function store(Request $request, Card $card): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$card->checklists()->create($request->validate([
			'title' => ['required', 'string', 'max:255'],
		]));

		return back();
	}

	/**
	 * Rename a checklist.
	 */
	public function update(Request $request, Checklist $checklist): RedirectResponse
	{
		Gate::authorize('update', $checklist->card->stack->project);

		$checklist->update($request->validate([
			'title' => ['required', 'string', 'max:255'],
		]));

		return back();
	}

	/**
	 * Delete a checklist and its items.
	 */
	public function destroy(Checklist $checklist): RedirectResponse
	{
		Gate::authorize('update', $checklist->card->stack->project);

		$checklist->delete();

		return back();
	}
}
