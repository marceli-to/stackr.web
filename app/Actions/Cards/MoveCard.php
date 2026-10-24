<?php

namespace App\Actions\Cards;

use App\Models\Attachment;
use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MoveCard
{
	public function __construct(private CarryCardOver $carryCardOver) {}

	/**
	 * Places the card at the given index among the target list's other cards
	 * (the ones on the board; archived cards keep their places). The list
	 * may be on another project's board; the card's files move with it.
	 */
	public function handle(Card $card, Stack $target, int $index): void
	{
		$from = $card->stack->project_id;

		DB::transaction(function () use ($card, $target, $index, $from): void {
			$siblings = $target->cards()
				->active()
				->whereKeyNot($card->id)
				->get(['id', 'position'])
				->map(fn (Card $sibling): string => $sibling->position)
				->all();

			$card->stack()->associate($target);
			$card->position = FractionalIndex::forIndex($siblings, $index);
			$card->save();

			if ($target->project_id !== $from) {
				$this->carryCardOver->handle($card, $target->project);
				$this->moveFiles($card, $from, $target->project_id);
			}
		});
	}

	private function moveFiles(Card $card, int $from, int $to): void
	{
		$disk = Storage::disk(Attachment::DISK);
		$old = Attachment::directoryFor($from, $card->id);
		$new = Attachment::directoryFor($to, $card->id);

		foreach ($card->attachments()->get() as $attachment) {
			$path = $new.'/'.basename($attachment->path);

			if ($disk->exists($attachment->path)) {
				$disk->move($attachment->path, $path);
			}

			$attachment->update(['path' => $path]);
		}

		$disk->deleteDirectory($old);
	}
}
