<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * An entry in the bell.
 *
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			...$this->data,
			'read' => $this->read_at !== null,
			'created_at' => $this->created_at,
		];
	}
}
