<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login()
    {
        $this->get(route('home'))->assertRedirect(route('login'));
    }

    public function test_signed_in_users_are_sent_to_their_projects()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertRedirect(route('projects.index'));
    }
}
