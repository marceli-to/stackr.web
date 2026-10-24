<?php

namespace App\Http\Controllers;

use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChecklistItemController extends Controller
{
    /**
     * Add an item to the end of a checklist.
     */
    public function store(Request $request, Checklist $checklist): RedirectResponse
    {
        Gate::authorize('update', $checklist->card->stack->project);

        $checklist->items()->create($request->validate([
            'title' => ['required', 'string', 'max:500'],
        ]));

        return back();
    }

    /**
     * Rename an item, or tick it off and back on.
     */
    public function update(Request $request, ChecklistItem $item): RedirectResponse
    {
        Gate::authorize('update', $item->checklist->card->stack->project);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:500'],
            'done' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('done', $data)) {
            $item->completed_at = $request->boolean('done') ? ($item->completed_at ?? now()) : null;
        }

        $item->fill(array_intersect_key($data, ['title' => true]))->save();

        return back();
    }

    /**
     * Delete an item.
     */
    public function destroy(ChecklistItem $item): RedirectResponse
    {
        Gate::authorize('update', $item->checklist->card->stack->project);

        $item->delete();

        return back();
    }
}
