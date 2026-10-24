<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProjectInvitationController extends Controller
{
    /**
     * Open an invitation link. Guests sign up (or log in) first and come back
     * here; then the signed-in user joins the project.
     */
    public function show(Request $request, string $token): RedirectResponse
    {
        $invitation = ProjectInvitation::with('project')->where('token', $token)->first();

        if ($invitation === null || $invitation->isExpired()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This invitation is no longer valid. Ask for a new one.')]);

            return to_route('home');
        }

        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            $request->session()->put('url.intended', $request->fullUrl());

            return User::where('email', $invitation->email)->exists()
                ? to_route('login', ['email' => $invitation->email])
                : to_route('register', ['email' => $invitation->email]);
        }

        $project = $invitation->project;

        if ($project->roleOf($user) === null) {
            $project->members()->attach($user, ['role' => ProjectRole::Member->value]);
        }

        // The link arrived at this address, which proves the user owns it.
        if (Str::lower($user->email) === $invitation->email && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Welcome to :project.', ['project' => $project->name])]);

        return to_route('projects.show', $project);
    }

    /**
     * Withdraw an invitation that hasn't been accepted.
     */
    public function destroy(ProjectInvitation $invitation): RedirectResponse
    {
        Gate::authorize('manageMembers', $invitation->project);

        $invitation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation withdrawn.')]);

        return back();
    }
}
