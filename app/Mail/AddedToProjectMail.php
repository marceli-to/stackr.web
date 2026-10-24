<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells someone who already has an account that they were added to a project.
 */
class AddedToProjectMail extends Mailable implements ShouldQueue
{
	use Queueable, SerializesModels;

	public function __construct(public Project $project, public User $inviter) {}

	public function envelope(): Envelope
	{
		return new Envelope(
			subject: __(':name added you to :project', [
				'name' => $this->inviter->name,
				'project' => $this->project->name,
			]),
		);
	}

	public function content(): Content
	{
		return new Content(
			markdown: 'mail.added-to-project',
			with: [
				'inviter' => $this->inviter->name,
				'project' => $this->project->name,
				'url' => route('projects.show', $this->project),
			],
		);
	}
}
