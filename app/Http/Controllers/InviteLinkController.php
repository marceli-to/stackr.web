<?php

namespace App\Http\Controllers;

use App\Actions\Members\DisableInviteLink;
use App\Actions\Members\JoinByLink;
use App\Actions\Members\ResetInviteLink;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InviteLinkController extends Controller
{
	/**
	 * Open a project's join link. Guests sign up (or log in) first and come
	 * back here; then the signed-in user joins the project.
	 */
	public function show(Request $request, string $token, JoinByLink $joinByLink): RedirectResponse
	{
		$project = Project::where('invite_token', $token)->first();

		if ($project === null) {
			$this->toast(__('This invitation is no longer valid. Ask for a new one.'), 'error');

			return to_route('home');
		}

		/** @var User|null $user */
		$user = $request->user();

		if ($user === null) {
			$request->session()->put('url.intended', $request->fullUrl());

			return to_route('register');
		}

		$joinByLink->handle($project, $user);

		$this->toast(__('Welcome to :project.', ['project' => $project->name]));

		return to_route('projects.show', $project);
	}

	/**
	 * Turn the join link on, or replace it with a new one.
	 */
	public function store(Project $project, ResetInviteLink $resetInviteLink): RedirectResponse
	{
		Gate::authorize('manageMembers', $project);

		$hadLink = $project->invite_token !== null;

		$resetInviteLink->handle($project);

		$this->toast($hadLink ? __('New link created; the old one no longer works.') : __('Invite link created.'));

		return back();
	}

	/**
	 * Turn the join link off.
	 */
	public function destroy(Project $project, DisableInviteLink $disableInviteLink): RedirectResponse
	{
		Gate::authorize('manageMembers', $project);

		$disableInviteLink->handle($project);

		$this->toast(__('Invite link turned off.'));

		return back();
	}
}
