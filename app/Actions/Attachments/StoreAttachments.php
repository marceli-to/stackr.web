<?php

namespace App\Actions\Attachments;

use App\Models\Attachment;
use App\Models\Card;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StoreAttachments
{
	/**
	 * Stores the files on the card. The first image becomes the cover when
	 * the card has none yet, as on Trello.
	 *
	 * @param  list<UploadedFile>  $files
	 */
	public function handle(Card $card, User $user, array $files): void
	{
		$directory = Attachment::directoryFor($card->stack->project_id, $card->id);

		foreach ($files as $file) {
			$extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
			$path = $file->storeAs($directory, Str::uuid().($extension !== '' ? ".{$extension}" : ''), Attachment::DISK);
			$size = str_starts_with((string) $file->getMimeType(), 'image/') ? @getimagesize($file->getRealPath()) : false;

			$attachment = new Attachment([
				'path' => $path,
				'name' => Str::limit($file->getClientOriginalName(), 250, ''),
				'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
				'size' => $file->getSize(),
				'width' => $size !== false ? $size[0] : null,
				'height' => $size !== false ? $size[1] : null,
			]);
			$attachment->card()->associate($card);
			$attachment->user()->associate($user);
			$attachment->save();

			if ($card->cover_attachment_id === null && $attachment->isImage()) {
				$card->update(['cover_attachment_id' => $attachment->id]);
			}
		}
	}
}
