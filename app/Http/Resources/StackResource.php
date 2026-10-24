<?php

namespace App\Http\Resources;

use App\Models\Stack;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A list on the board with its card faces.
 *
 * @mixin Stack
 */
class StackResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'wip_limit' => $this->wip_limit,
			'cards' => CardFaceResource::collection($this->cards),
		];
	}
}
