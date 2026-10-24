<?php

namespace App\Http\Controllers;

use App\Actions\Projects\CreateProject;
use App\Actions\Projects\DeleteProject;
use App\Actions\Projects\UpdateProject;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\CardResource;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\LabelResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\ProjectTileResource;
use App\Http\Resources\StackResource;
use App\Models\Project;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
		/** @var User $user */
		$user = $request->user();

		$memberships = $user->memberships()
			->with(['project' => fn ($query) => $query->withCount(['stacks', 'cards'])])
			->get()
			->sortBy('project.name', SORT_NATURAL | SORT_FLAG_CASE)
			->values();

		return Inertia::render('projects/Index', [
			'projects' => ProjectTileResource::collection($memberships),
		]);
	}

	/**
	 * Create a project owned by the user.
	 */
	public function store(ProjectRequest $request, CreateProject $createProject): RedirectResponse
	{
		/** @var User $user */
		$user = $request->user();

		$project = $createProject->handle($user, $request->validated());

		$this->toast(__('Project created.'));

		return to_route('projects.show', $project);
	}

	/**
	 * Show a project's board. ?card=<id> opens a card; it has to be on this board.
	 */
	public function show(Request $request, Project $project): Response
	{
		Gate::authorize('view', $project);

		$project->load([
			'labels',
			'stacks.cards' => fn ($query) => $query->withCount(['attachments', 'comments']),
			'stacks.cards.labels:id',
			'stacks.cards.members:id',
			'stacks.cards.cover',
			'stacks.cards.checklists.items:id,checklist_id,completed_at',
		]);

		$open = $request->integer('card') ?: null;
		$card = $open !== null
			? $project->stacks->flatMap(fn (Stack $stack) => $stack->cards)->firstWhere('id', $open)
			: null;

		$manages = $request->user()?->can('manageMembers', $project) ?? false;

		return Inertia::render('projects/Show', [
			'project' => new ProjectResource($project),
			'labels' => LabelResource::collection($project->labels),
			'members' => MemberResource::collection($project->membershipsInOrder()),
			'invitations' => $manages ? InvitationResource::collection($project->invitations()->get()) : [],
			'canManageMembers' => $manages,
			'stacks' => StackResource::collection($project->stacks),
			'card' => $card !== null ? new CardResource($card) : null,
		]);
	}

	/**
	 * Rename a project or change its description.
	 */
	public function update(ProjectRequest $request, Project $project, UpdateProject $updateProject): RedirectResponse
	{
		Gate::authorize('update', $project);

		$updateProject->handle($project, $request->validated());

		$this->toast(__('Project updated.'));

		return back();
	}

	/**
	 * Delete a project with all of its lists and cards.
	 */
	public function destroy(Project $project, DeleteProject $deleteProject): RedirectResponse
	{
		Gate::authorize('delete', $project);

		$deleteProject->handle($project);

		$this->toast(__('Project deleted.'));

		return to_route('projects.index');
	}
}
