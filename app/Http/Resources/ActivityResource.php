<?php

namespace App\Http\Resources;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An entry in a card's activity log. The dialog turns type and data into
 * a sentence.
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'type' => $this->type,
			'data' => (object) ($this->data ?? []),
			'user' => $this->user?->only('id', 'name'),
			'via_agent' => $this->via_agent,
			'created_at' => $this->created_at,
		];
	}
}
