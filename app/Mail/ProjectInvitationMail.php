<?php

namespace App\Mail;

use App\Models\ProjectInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invites someone to a project by email. The link works for new and existing accounts.
 */
class ProjectInvitationMail extends Mailable
{
	use Queueable, SerializesModels;

	public function __construct(public ProjectInvitation $invitation) {}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: __(':name invited you to :project', [
				'name' => $this->invitation->inviter->name ?? config('app.name'),
				'project' => $this->invitation->project->name,
			]),
		);
	}

	public function content(): Content
	{
		return new Content(
			markdown: 'mail.project-invitation',
			with: [
				'inviter' => $this->invitation->inviter->name ?? null,
				'project' => $this->invitation->project->name,
				'url' => route('invitations.show', $this->invitation->token),
				'days' => ProjectInvitation::VALID_DAYS,
			],
		);
	}
}
