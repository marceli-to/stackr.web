<?php

namespace App\Http\Controllers;

use App\Actions\Members\InviteMember;
use App\Actions\Members\RemoveMember;
use App\Enums\ProjectRole;
use App\Http\Requests\InviteMemberRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectMemberController extends Controller
{
	/**
	 * Invite someone by email. People with an account are added right away;
	 * everyone else gets a link to sign up and join.
	 */
	public function store(InviteMemberRequest $request, Project $project, InviteMember $inviteMember): RedirectResponse
	{
		Gate::authorize('manageMembers', $project);

		/** @var User $inviter */
		$inviter = $request->user();

		$invited = $inviteMember->handle($project, $inviter, $request->email());

		$this->toast($invited instanceof User
			? __(':name was added.', ['name' => $invited->name])
			: __('Invitation sent to :email.', ['email' => $invited->email]));

		return back();
	}

	/**
	 * Remove a member, or leave the project yourself. The owner can't be removed.
	 */
	public function destroy(Request $request, Project $project, User $user, RemoveMember $removeMember): RedirectResponse
	{
		$leaving = $request->user()?->is($user) ?? false;

		if (! $leaving) {
			Gate::authorize('manageMembers', $project);
		}

		$role = $project->roleOf($user);

		abort_if($role === null, 404);
		abort_if($role === ProjectRole::Owner, 403, __('The owner cannot leave or be removed.'));

		$removeMember->handle($project, $user);

		if ($leaving) {
			$this->toast(__('You left :project.', ['project' => $project->name]));

			return to_route('projects.index');
		}

		$this->toast(__(':name was removed.', ['name' => $user->name]));

		return back();
	}
}
