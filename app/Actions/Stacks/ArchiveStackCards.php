<?php

namespace App\Actions\Stacks;

use App\Actions\Cards\RecordActivity;
use App\Models\Stack;
use Illuminate\Support\Facades\DB;

class ArchiveStackCards
{
	public function __construct(private RecordActivity $recordActivity) {}

	/**
	 * Archives every card in the list; the list itself stays. Each card logs
	 * it and keeps its place for a restore; nobody is notified.
	 *
	 * @return int how many cards were archived
	 */
	public function handle(Stack $stack): int
	{
		return DB::transaction(function () use ($stack): int {
			$cards = $stack->cards()->active()->get();

			foreach ($cards as $card) {
				$card->update(['archived_at' => now()]);
				$this->recordActivity->handle($card, 'archived', notify: false);
			}

			return $cards->count();
		});
	}
}
