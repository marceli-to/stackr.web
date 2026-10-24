<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\ProjectRequest;
use App\Models\Card;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Stack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Show the projects the user is a member of.
     */
    public function index(Request $request): Response
    {
        $projects = $request->user()->memberships()
            ->with(['project' => fn ($query) => $query->withCount(['stacks', 'cards'])])
            ->get()
            ->sortBy('project.name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->map(fn (ProjectMembership $membership) => [
                'id' => $membership->project->id,
                'name' => $membership->project->name,
                'description' => $membership->project->description,
                'role' => $membership->role,
                'stacks_count' => $membership->project->stacks_count,
                'cards_count' => $membership->project->cards_count,
                'updated_at' => $membership->project->updated_at,
            ]);

        return Inertia::render('projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Create a project owned by the user.
     */
    public function store(ProjectRequest $request): RedirectResponse
    {
        $project = DB::transaction(function () use ($request): Project {
            $project = Project::create($request->validated());
            $project->members()->attach($request->user(), ['role' => ProjectRole::Owner->value]);

            return $project;
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project created.')]);

        return to_route('projects.show', $project);
    }

    /**
     * Show a project's board.
     */
    public function show(Project $project): Response
    {
        Gate::authorize('view', $project);

        $project->load('stacks.cards');

        return Inertia::render('projects/Show', [
            'project' => $project->only('id', 'name', 'description'),
            'stacks' => $project->stacks->map(fn (Stack $stack) => [
                'id' => $stack->id,
                'name' => $stack->name,
                'cards' => $stack->cards->map(fn (Card $card) => [
                    'id' => $card->id,
                    'title' => $card->title,
                    'due_at' => $card->due_at,
                ]),
            ]),
        ]);
    }

    /**
     * Rename a project or change its description.
     */
    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $project->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project updated.')]);

        return back();
    }

    /**
     * Delete a project with all of its lists and cards.
     */
    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Project deleted.')]);

        return to_route('projects.index');
    }
}
