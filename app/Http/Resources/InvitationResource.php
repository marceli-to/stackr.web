<?php

namespace App\Http\Resources;

use App\Models\BoardInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BoardInvitation
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
