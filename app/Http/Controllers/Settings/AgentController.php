<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Agents\CreateAgentToken;
use App\Actions\Agents\RevokeAgentToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AgentTokenRequest;
use App\Http\Resources\AgentTokenResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class AgentController extends Controller
{
	/**
	 * Show the user's agent tokens and how to connect an agent.
	 */
	public function edit(Request $request): Response
	{
		return Inertia::render('settings/Agents', [
			'tokens' => AgentTokenResource::collection($request->user()->tokens()->latest('id')->get()),
			'mcpUrl' => url('mcp'),
		]);
	}

	/**
	 * Create a token; its plain text is shown once, on the next page view.
	 */
	public function store(AgentTokenRequest $request, CreateAgentToken $createAgentToken): RedirectResponse
	{
		$token = $createAgentToken->handle($request->user(), $request->validated('name'));

		Inertia::flash('agentToken', [
			'id' => $token->accessToken->id,
			'name' => $token->accessToken->name,
			'token' => $token->plainTextToken,
		]);

		return back();
	}

	/**
	 * Revoke a token; agents using it are signed out at once.
	 */
	public function destroy(Request $request, int $token, RevokeAgentToken $revokeAgentToken): RedirectResponse
	{
		/** @var PersonalAccessToken $found */
		$found = $request->user()->tokens()->findOrFail($token);

		$revokeAgentToken->handle($found);

		$this->toast(__('Token revoked.'));

		return back();
	}
}
