<?php

namespace App\Http\Controllers;

use App\Enums\LabelColor;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LabelController extends Controller
{
    /**
     * Add a label to a project's set.
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->labels()->create($this->validated($request));

        return back();
    }

    /**
     * Rename or recolour a label on every card that has it.
     */
    public function update(Request $request, Label $label): RedirectResponse
    {
        Gate::authorize('update', $label->project);

        $label->update($this->validated($request));

        return back();
    }

    /**
     * Delete a label; it comes off every card.
     */
    public function destroy(Label $label): RedirectResponse
    {
        Gate::authorize('update', $label->project);

        $label->delete();

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['nullable', 'string', 'max:60'],
            'color' => ['required', Rule::enum(LabelColor::class)],
        ]);
    }
}
