<?php

namespace App\Http\Resources;

use App\Models\BoardMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A board on the overview, seen through the user's membership. Expects
 * the board loaded with its stacks and cards counts.
 *
 * @mixin BoardMembership
 */
class BoardTileResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			...(new BoardResource($this->board))->toArray($request),
			'role' => $this->role,
			'stacks_count' => $this->board->stacks_count,
			'cards_count' => $this->board->cards_count,
			'updated_at' => $this->board->updated_at,
		];
	}
}
