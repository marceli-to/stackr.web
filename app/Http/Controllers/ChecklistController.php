<?php

namespace App\Http\Controllers;

use App\Actions\Checklists\CreateChecklist;
use App\Actions\Checklists\DeleteChecklist;
use App\Actions\Checklists\RenameChecklist;
use App\Http\Requests\ChecklistRequest;
use App\Models\Card;
use App\Models\Checklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ChecklistController extends Controller
{
	/**
	 * Add a checklist to a card.
	 */
	public function store(ChecklistRequest $request, Card $card, CreateChecklist $createChecklist): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$createChecklist->handle($card, $request->validated('title'));

		return back();
	}

	/**
	 * Rename a checklist.
	 */
	public function update(ChecklistRequest $request, Checklist $checklist, RenameChecklist $renameChecklist): RedirectResponse
	{
		Gate::authorize('update', $checklist->card->stack->project);

		$renameChecklist->handle($checklist, $request->validated('title'));

		return back();
	}

	/**
	 * Delete a checklist and its items.
	 */
	public function destroy(Checklist $checklist, DeleteChecklist $deleteChecklist): RedirectResponse
	{
		Gate::authorize('update', $checklist->card->stack->project);

		$deleteChecklist->handle($checklist);

		return back();
	}
}
