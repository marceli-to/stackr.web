<?php

namespace App\Mail;

use App\Models\BoardInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Invites someone to a board by email. The link works for new and existing accounts.
 */
class BoardInvitationMail extends Mailable implements ShouldQueue
{
	use Queueable, SerializesModels;

	public function __construct(public BoardInvitation $invitation) {}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: __(':name invited you to :board', [
				'name' => $this->invitation->inviter->name ?? config('app.name'),
				'board' => $this->invitation->board->name,
			]),
		);
	}

	public function content(): Content
	{
		return new Content(
			markdown: 'mail.board-invitation',
			with: [
				'inviter' => $this->invitation->inviter->name ?? null,
				'board' => $this->invitation->board->name,
				'url' => route('invitations.show', $this->invitation->token),
				'days' => BoardInvitation::VALID_DAYS,
			],
		);
	}
}
