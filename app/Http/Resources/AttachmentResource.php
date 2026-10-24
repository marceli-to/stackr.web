<?php

namespace App\Http\Resources;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attachment
 */
class AttachmentResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'mime_type' => $this->mime_type,
			'size' => $this->size,
			'is_image' => $this->isImage(),
			'url' => route('attachments.show', $this->resource),
			'download_url' => route('attachments.show', ['attachment' => $this->resource, 'download' => 1]),
			'width' => $this->width,
			'height' => $this->height,
			'user' => $this->user?->name,
			'created_at' => $this->created_at,
		];
	}
}
