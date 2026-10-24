<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgentTokensTest extends TestCase
{
	use RefreshDatabase;

	public function test_the_agents_page_asks_for_the_password_first()
	{
		$this->actingAs(User::factory()->create())
			->get(route('agents.edit'))
			->assertRedirect(route('password.confirm'));
	}

	public function test_the_agents_page_lists_the_users_tokens()
	{
		$user = User::factory()->create();
		$user->createToken('Laptop');
		User::factory()->create()->createToken('Not mine');

		$this->actingAs($user)
			->withSession(['auth.password_confirmed_at' => time()])
			->get(route('agents.edit'))
			->assertInertia(fn (Assert $page) => $page
				->component('settings/Agents')
				->has('tokens', 1)
				->where('tokens.0.name', 'Laptop')
				->where('tokens.0.last_used_at_diff', null)
				->where('mcpUrl', url('mcp'))
				->missing('tokens.0.token'),
			);
	}

	public function test_a_new_token_is_shown_once()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->withSession(['auth.password_confirmed_at' => time()])
			->post(route('agents.tokens.store'), ['name' => 'Laptop'])
			->assertRedirect()
			->assertInertiaFlash('agentToken.name', 'Laptop');

		$token = $user->tokens()->sole();
		$this->assertSame('Laptop', $token->name);
	}

	public function test_a_token_needs_a_name()
	{
		$this->actingAs(User::factory()->create())
			->withSession(['auth.password_confirmed_at' => time()])
			->post(route('agents.tokens.store'), ['name' => ''])
			->assertSessionHasErrors('name');
	}

	public function test_users_revoke_only_their_own_tokens()
	{
		$user = User::factory()->create();
		$mine = $user->createToken('Laptop')->accessToken;
		$theirs = User::factory()->create()->createToken('Theirs')->accessToken;

		$this->actingAs($user)->delete(route('agents.tokens.destroy', $theirs->id))->assertNotFound();
		$this->actingAs($user)->delete(route('agents.tokens.destroy', $mine->id))->assertRedirect();

		$this->assertModelMissing($mine);
		$this->assertModelExists($theirs);
	}
}
