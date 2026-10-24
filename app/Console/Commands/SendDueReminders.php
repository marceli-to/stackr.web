<?php

namespace App\Console\Commands;

use App\Models\Card;
use App\Notifications\CardDueSoon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Reminds a card's members once when it's due within a day. Runs from the
 * scheduler (routes/console.php), which the server's cron starts every minute.
 */
#[Signature('app:send-due-reminders')]
#[Description('Notify card members about cards that are due within a day')]
class SendDueReminders extends Command
{
	public function handle(): int
	{
		$cards = Card::query()
			->active()
			->whereHas('stack', fn ($query) => $query->active())
			->whereNull('completed_at')
			->whereNull('due_reminded_at')
			->whereBetween('due_at', [now(), now()->addDay()])
			->with(['members', 'stack.project'])
			->get();

		foreach ($cards as $card) {
			Notification::send($card->members, new CardDueSoon($card));
			$card->update(['due_reminded_at' => now()]);
		}

		$this->info("Reminded members of {$cards->count()} cards.");

		return self::SUCCESS;
	}
}
