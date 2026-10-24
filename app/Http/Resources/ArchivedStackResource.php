<?php

namespace App\Http\Resources;

use App\Models\Stack;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A list in the board's archive. Expects the cards counted.
 *
 * @mixin Stack
 */
class ArchivedStackResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'cards_count' => $this->cards_count ?? 0,
			'archived_at' => $this->archived_at,
		];
	}
}
