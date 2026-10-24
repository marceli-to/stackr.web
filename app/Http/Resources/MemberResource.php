<?php

namespace App\Http\Resources;

use App\Models\BoardMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BoardMembership
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
