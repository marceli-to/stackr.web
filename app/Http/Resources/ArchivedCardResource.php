<?php

namespace App\Http\Resources;

use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A card in the board's archive. Expects the stack loaded.
 *
 * @mixin Card
 */
class ArchivedCardResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'title' => $this->title,
			'stack' => $this->stack->name,
			'archived_at' => $this->archived_at,
		];
	}
}
