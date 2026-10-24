<?php

namespace App\Actions\Members;

use App\Enums\BoardRole;
use App\Mail\AddedToBoardMail;
use App\Mail\BoardInvitationMail;
use App\Models\Board;
use App\Models\BoardInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class InviteMember
{
	/**
	 * People with an account are added right away and get a note; everyone
	 * else gets a link to sign up and join. Returns whom it added or invited.
	 */
	public function handle(Board $board, User $inviter, string $email): User|BoardInvitation
	{
		$user = User::where('email', $email)->first();

		if ($user !== null) {
			$board->members()->attach($user, ['role' => BoardRole::Member->value]);
			$board->invitations()->where('email', $email)->delete();

			// Sent right away: the shared host runs no queue worker.
			Mail::to($user)->send(new AddedToBoardMail($board, $inviter));

			return $user;
		}

		// Inviting the same address again renews the link and sends it once more.
		$invitation = $board->invitations()->updateOrCreate(
			['email' => $email],
			['token' => BoardInvitation::newToken(), 'invited_by' => $inviter->id],
		);
		$invitation->touch();

		Mail::to($email)->send(new BoardInvitationMail($invitation));

		return $invitation;
	}
}
