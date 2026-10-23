<?php

namespace App\Http\Controllers;

use App\Http\Requests\MoveRequest;
use App\Http\Requests\StackRequest;
use App\Models\Project;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StackController extends Controller
{
    /**
     * Add a list to the end of a project's board.
     */
    public function store(StackRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        DB::transaction(function () use ($request, $project): void {
            $last = $project->stacks()->reorder()->max('position');

            $project->stacks()->create([
                ...$request->validated(),
                'position' => FractionalIndex::between($last, null),
            ]);
        });

        return back();
    }

    /**
     * Rename a list.
     */
    public function update(StackRequest $request, Stack $stack): RedirectResponse
    {
        Gate::authorize('update', $stack->project);

        $stack->update($request->validated());

        return back();
    }

    /**
     * Move a list to a new place on the board.
     */
    public function move(MoveRequest $request, Stack $stack): RedirectResponse
    {
        Gate::authorize('update', $stack->project);

        DB::transaction(function () use ($request, $stack): void {
            $siblings = $stack->project->stacks()
                ->whereKeyNot($stack->id)
                ->get(['id', 'position'])
                ->map(fn (Stack $sibling): string => $sibling->position)
                ->all();

            $stack->update(['position' => FractionalIndex::forIndex($siblings, $request->integer('index'))]);
        });

        return back();
    }

    /**
     * Delete a list and its cards.
     */
    public function destroy(Stack $stack): RedirectResponse
    {
        Gate::authorize('update', $stack->project);

        $stack->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('List deleted.')]);

        return back();
    }
}
