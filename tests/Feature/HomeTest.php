<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeTest extends TestCase
{
	use RefreshDatabase;

	public function test_guests_see_the_landing_page()
	{
		$this->get(route('home'))
			->assertOk()
			->assertInertia(fn (Assert $page) => $page->component('site/Welcome'));
	}

	public function test_guests_see_the_legal_notice()
	{
		$this->get(route('legal'))
			->assertOk()
			->assertInertia(fn (Assert $page) => $page->component('site/Legal'));
	}

	public function test_signed_in_users_are_sent_to_their_boards()
	{
		$this->actingAs(User::factory()->create())
			->get(route('home'))
			->assertRedirect(route('boards.index'));
	}
}
