<?php

namespace App\Mail;

use App\Models\Board;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells someone who already has an account that they were added to a board.
 */
class AddedToBoardMail extends Mailable implements ShouldQueue
{
	use Queueable, SerializesModels;

	public function __construct(public Board $board, public User $inviter) {}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: __(':name added you to :board', [
				'name' => $this->inviter->name,
				'board' => $this->board->name,
			]),
		);
	}

	public function content(): Content
	{
		return new Content(
			markdown: 'mail.added-to-board',
			with: [
				'inviter' => $this->inviter->name,
				'board' => $this->board->name,
				'url' => route('boards.show', $this->board),
			],
		);
	}
}
