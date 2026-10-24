<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\Stack;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A board a card can be moved or copied to, with its lists. Expects the
 * active stacks loaded with their active cards counted.
 *
 * @mixin Project
 */
class DestinationResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'stacks' => $this->stacks->map(fn (Stack $stack): array => [
				'id' => $stack->id,
				'name' => $stack->name,
				'cards_count' => $stack->cards_count ?? 0,
			]),
		];
	}
}
