<?php

namespace App\Actions\Agents;

use Laravel\Sanctum\PersonalAccessToken;

class RevokeAgentToken
{
	public function handle(PersonalAccessToken $token): void
	{
		$token->delete();
	}
}
