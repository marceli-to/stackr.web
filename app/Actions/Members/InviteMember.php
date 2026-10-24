<?php

namespace App\Actions\Members;

use App\Enums\ProjectRole;
use App\Mail\AddedToProjectMail;
use App\Mail\ProjectInvitationMail;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class InviteMember
{
	/**
	 * People with an account are added right away and get a note; everyone
	 * else gets a link to sign up and join. Returns whom it added or invited.
	 */
	public function handle(Project $project, User $inviter, string $email): User|ProjectInvitation
	{
		$user = User::where('email', $email)->first();

		if ($user !== null) {
			$project->members()->attach($user, ['role' => ProjectRole::Member->value]);
			$project->invitations()->where('email', $email)->delete();

			// Sent right away: the shared host runs no queue worker.
			Mail::to($user)->send(new AddedToProjectMail($project, $inviter));

			return $user;
		}

		// Inviting the same address again renews the link and sends it once more.
		$invitation = $project->invitations()->updateOrCreate(
			['email' => $email],
			['token' => ProjectInvitation::newToken(), 'invited_by' => $inviter->id],
		);
		$invitation->touch();

		Mail::to($email)->send(new ProjectInvitationMail($invitation));

		return $invitation;
	}
}
