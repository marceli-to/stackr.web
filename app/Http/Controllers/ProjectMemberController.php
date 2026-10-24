<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Mail\AddedToProjectMail;
use App\Mail\ProjectInvitationMail;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProjectMemberController extends Controller
{
	/**
	 * Invite someone by email. People with an account are added right away;
	 * everyone else gets a link to sign up and join.
	 */
	public function store(Request $request, Project $project): RedirectResponse
	{
		Gate::authorize('manageMembers', $project);

		$email = Str::lower(trim((string) $request->validate([
			'email' => ['required', 'string', 'email', 'max:255'],
		])['email']));

		/** @var User $inviter */
		$inviter = $request->user();
		$user = User::where('email', $email)->first();

		if ($user !== null) {
			if ($project->roleOf($user) !== null) {
				throw ValidationException::withMessages([
					'email' => __(':name is already on this project.', ['name' => $user->name]),
				]);
			}

			$project->members()->attach($user, ['role' => ProjectRole::Member->value]);
			$project->invitations()->where('email', $email)->delete();

			// Sent right away: the shared host runs no queue worker.
			Mail::to($user)->send(new AddedToProjectMail($project, $inviter));

			Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was added.', ['name' => $user->name])]);

			return back();
		}

		// Inviting the same address again renews the link and sends it once more.
		$invitation = $project->invitations()->updateOrCreate(
			['email' => $email],
			['token' => ProjectInvitation::newToken(), 'invited_by' => $inviter->id],
		);
		$invitation->touch();

		Mail::to($email)->send(new ProjectInvitationMail($invitation));

		Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent to :email.', ['email' => $email])]);

		return back();
	}

	/**
	 * Remove a member, or leave the project yourself. The owner can't be removed.
	 */
	public function destroy(Request $request, Project $project, User $user): RedirectResponse
	{
		$leaving = $request->user()->is($user);

		if (! $leaving) {
			Gate::authorize('manageMembers', $project);
		}

		$role = $project->roleOf($user);

		abort_if($role === null, 404);
		abort_if($role === ProjectRole::Owner, 403, __('The owner cannot leave or be removed.'));

		DB::transaction(function () use ($project, $user): void {
			$cards = $project->cards()->pluck('cards.id');

			DB::table('card_user')->where('user_id', $user->id)->whereIn('card_id', $cards)->delete();
			$project->members()->detach($user);
		});

		if ($leaving) {
			Inertia::flash('toast', ['type' => 'success', 'message' => __('You left :project.', ['project' => $project->name])]);

			return to_route('projects.index');
		}

		Inertia::flash('toast', ['type' => 'success', 'message' => __(':name was removed.', ['name' => $user->name])]);

		return back();
	}
}
