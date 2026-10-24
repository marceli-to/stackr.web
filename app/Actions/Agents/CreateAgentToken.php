<?php

namespace App\Actions\Agents;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

class CreateAgentToken
{
	/**
	 * A token that acts as the user on all their boards, until revoked.
	 * The plain text is only available now.
	 */
	public function handle(User $user, string $name): NewAccessToken
	{
		return $user->createToken($name);
	}
}
