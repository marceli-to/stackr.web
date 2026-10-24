<?php

namespace App\Http\Resources;

use App\Models\ProjectInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectInvitation
 */
class InvitationResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array
	{
		return [
			'id' => $this->id,
			'email' => $this->email,
			'expired' => $this->isExpired(),
		];
	}
}
