<?php

namespace App\Http\Controllers;

use App\Actions\Checklists\CreateChecklistItem;
use App\Actions\Checklists\DeleteChecklistItem;
use App\Actions\Checklists\UpdateChecklistItem;
use App\Http\Requests\ChecklistItemRequest;
use App\Http\Requests\ChecklistItemUpdateRequest;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ChecklistItemController extends Controller
{
	/**
	 * Add an item to the end of a checklist.
	 */
	public function store(ChecklistItemRequest $request, Checklist $checklist, CreateChecklistItem $createItem): RedirectResponse
	{
		Gate::authorize('update', $checklist->card->stack->project);

		$createItem->handle($checklist, $request->validated('title'));

		return back();
	}

	/**
	 * Rename an item, or tick it off and back on.
	 */
	public function update(ChecklistItemUpdateRequest $request, ChecklistItem $item, UpdateChecklistItem $updateItem): RedirectResponse
	{
		Gate::authorize('update', $item->checklist->card->stack->project);

		$updateItem->handle($item, $request->validated());

		return back();
	}

	/**
	 * Delete an item.
	 */
	public function destroy(ChecklistItem $item, DeleteChecklistItem $deleteItem): RedirectResponse
	{
		Gate::authorize('update', $item->checklist->card->stack->project);

		$deleteItem->handle($item);

		return back();
	}
}
