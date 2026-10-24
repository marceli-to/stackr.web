<?php

namespace App\Actions\Boards;

use App\Models\Attachment;
use App\Models\Board;
use App\Support\Thumbnail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SetBackground
{
	/** Wide enough for large screens, small enough to load quickly. */
	public const WIDTH = 2400;

	/**
	 * Stores the photo behind the board, shrunk to WebP when it's large,
	 * and replaces the old one. Null removes it.
	 */
	public function handle(Board $board, ?UploadedFile $image): void
	{
		$disk = Storage::disk(Attachment::DISK);
		$old = $board->background_path;
		$path = null;

		if ($image !== null) {
			$bytes = Thumbnail::make($image->getRealPath(), (string) $image->getMimeType(), self::WIDTH);
			$path = $bytes !== null
				? "backgrounds/{$board->id}/".Str::uuid().'.webp'
				: $image->storeAs("backgrounds/{$board->id}", Str::uuid().'.'.$image->guessExtension(), Attachment::DISK);

			if ($bytes !== null && $path !== false) {
				$disk->put($path, $bytes);
			}
		}

		$board->update(['background_path' => $path ?: null]);

		if ($old !== null) {
			$disk->delete($old);
		}
	}
}
