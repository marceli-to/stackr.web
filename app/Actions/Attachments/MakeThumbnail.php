<?php

namespace App\Actions\Attachments;

use App\Models\Attachment;
use App\Support\Thumbnail;
use Illuminate\Support\Facades\Storage;

class MakeThumbnail
{
	/**
	 * Saves a thumbnail next to a large image, as thumb_<name>.webp.
	 */
	public function handle(Attachment $attachment): void
	{
		$disk = Storage::disk(Attachment::DISK);

		if (! $attachment->isImage() || ! $disk->exists($attachment->path)) {
			return;
		}

		$bytes = Thumbnail::make($disk->path($attachment->path), $attachment->mime_type);

		if ($bytes === null) {
			return;
		}

		$path = dirname($attachment->path).'/thumb_'.pathinfo($attachment->path, PATHINFO_FILENAME).'.webp';
		$disk->put($path, $bytes);

		$attachment->update(['thumb_path' => $path]);
	}
}
