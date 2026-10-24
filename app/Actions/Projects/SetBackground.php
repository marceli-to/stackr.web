<?php

namespace App\Actions\Projects;

use App\Models\Attachment;
use App\Models\Project;
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
	public function handle(Project $project, ?UploadedFile $image): void
	{
		$disk = Storage::disk(Attachment::DISK);
		$old = $project->background_path;
		$path = null;

		if ($image !== null) {
			$bytes = Thumbnail::make($image->getRealPath(), (string) $image->getMimeType(), self::WIDTH);
			$path = $bytes !== null
				? "backgrounds/{$project->id}/".Str::uuid().'.webp'
				: $image->storeAs("backgrounds/{$project->id}", Str::uuid().'.'.$image->guessExtension(), Attachment::DISK);

			if ($bytes !== null && $path !== false) {
				$disk->put($path, $bytes);
			}
		}

		$project->update(['background_path' => $path ?: null]);

		if ($old !== null) {
			$disk->delete($old);
		}
	}
}
