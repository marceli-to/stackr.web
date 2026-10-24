<?php

namespace App\Http\Resources;

use App\Models\ProjectMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectMembership
 */
class MemberResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->user->id,
			'name' => $this->user->name,
			'email' => $this->user->email,
			'role' => $this->role,
		];
	}
}
