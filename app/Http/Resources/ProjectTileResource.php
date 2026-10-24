<?php

namespace App\Http\Resources;

use App\Models\ProjectMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A project on the overview, seen through the user's membership. Expects
 * the project loaded with its stacks and cards counts.
 *
 * @mixin ProjectMembership
 */
class ProjectTileResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			...(new ProjectResource($this->project))->toArray($request),
			'role' => $this->role,
			'stacks_count' => $this->project->stacks_count,
			'cards_count' => $this->project->cards_count,
			'updated_at' => $this->project->updated_at,
		];
	}
}
