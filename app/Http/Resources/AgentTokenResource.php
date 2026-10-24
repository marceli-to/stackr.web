<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * A personal access token as the Agents settings list it; never the token itself.
 *
 * @mixin PersonalAccessToken
 */
class AgentTokenResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'created_at_diff' => $this->created_at?->diffForHumans(),
			'last_used_at_diff' => $this->last_used_at?->diffForHumans(),
		];
	}
}
