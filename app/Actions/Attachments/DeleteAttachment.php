<?php

namespace App\Actions\Attachments;

use App\Models\Attachment;

class DeleteAttachment
{
	/**
	 * Deletes the file. A card that used it as its cover loses the cover.
	 */
	public function handle(Attachment $attachment): void
	{
		$attachment->delete();
	}
}
