<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class Agent
{
	/**
	 * Whether the request came in on a personal access token (the MCP
	 * server) rather than a browser session.
	 */
	public static function acting(): bool
	{
		$user = Auth::user();

		return $user instanceof User && $user->currentAccessToken() instanceof PersonalAccessToken;
	}
}
