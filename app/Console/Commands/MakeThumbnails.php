<?php

namespace App\Console\Commands;

use App\Actions\Attachments\MakeThumbnail;
use App\Models\Attachment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Makes the thumbnails that uploads from before thumbnails existed lack.
 * Safe to run again: images that have one, or don't need one, are skipped.
 */
#[Signature('app:make-thumbnails')]
#[Description('Create thumbnails for large images that have none')]
class MakeThumbnails extends Command
{
	public function handle(MakeThumbnail $makeThumbnail): int
	{
		$made = 0;

		Attachment::query()
			->whereNull('thumb_path')
			->where('mime_type', 'like', 'image/%')
			->lazyById()
			->each(function (Attachment $attachment) use ($makeThumbnail, &$made): void {
				$makeThumbnail->handle($attachment);
				$made += $attachment->thumb_path !== null ? 1 : 0;
			});

		$this->info("Made {$made} thumbnails.");

		return self::SUCCESS;
	}
}
