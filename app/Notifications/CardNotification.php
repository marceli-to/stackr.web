<?php

namespace App\Notifications;

use App\Models\Card;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Something about a card for one person: in the bell straight away, and
 * by email (queued) unless they turned that off in their profile.
 */
abstract class CardNotification extends Notification implements ShouldQueue
{
	use Queueable;

	public bool $deleteWhenMissingModels = true;

	public function __construct(public Card $card, public ?User $actor = null) {}

	/**
	 * What the bell shows after the actor's name, e.g. "mentioned you on".
	 */
	abstract protected function sentence(): string;

	abstract protected function subject(): string;

	/**
	 * A line of context under the sentence, e.g. the comment.
	 */
	protected function excerpt(): ?string
	{
		return null;
	}

	/**
	 * @return list<string>
	 */
	public function via(User $notifiable): array
	{
		return $notifiable->email_notifications ? ['database', 'mail'] : ['database'];
	}

	/**
	 * The bell gets it at once; only the email waits for the queue.
	 *
	 * @return array<string, string>
	 */
	public function viaConnections(): array
	{
		return ['database' => 'sync'];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(User $notifiable): array
	{
		$project = $this->card->stack->project;

		return [
			'card_id' => $this->card->id,
			'card_title' => $this->card->title,
			'project_id' => $project->id,
			'project_name' => $project->name,
			'actor' => $this->actor?->only('id', 'name'),
			'sentence' => $this->sentence(),
			'excerpt' => $this->excerpt(),
		];
	}

	public function toMail(User $notifiable): MailMessage
	{
		$project = $this->card->stack->project;
		$mail = (new MailMessage)
			->subject($this->subject())
			->greeting($this->card->title)
			->line(trim(($this->actor->name ?? '').' '.$this->sentence()).' “'.$this->card->title.'” on '.$project->name.'.');

		if ($this->excerpt() !== null) {
			$mail->line('> '.$this->excerpt());
		}

		return $mail
			->action(__('Open the card'), route('projects.show', [$project, 'card' => $this->card->id]))
			->salutation(__('You can turn these emails off in your profile settings.'));
	}
}
