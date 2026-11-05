<?php

namespace App\Actions\Cards;

use App\Models\Attachment;
use App\Models\Card;
use Illuminate\Support\Facades\Storage;

class MoveCardFiles
{
	/**
	 * Moves a card's files (and thumbnails) into the folder of the board it
	 * now belongs to.
	 */
	public function handle(Card $card, int $from, int $to): void
	{
		$disk = Storage::disk(Attachment::DISK);
		$old = Attachment::directoryFor($from, $card->id);
		$new = Attachment::directoryFor($to, $card->id);

		foreach ($card->attachments()->get() as $attachment) {
			$paths = [];

			foreach (['path', 'thumb_path'] as $column) {
				$current = $attachment->{$column};

				if ($current === null) {
					continue;
				}

				$paths[$column] = $new.'/'.basename($current);

				if ($disk->exists($current)) {
					$disk->move($current, $paths[$column]);
				}
			}

			$attachment->update($paths);
		}

		$disk->deleteDirectory($old);
	}
}
