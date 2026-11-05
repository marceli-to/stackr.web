<?php

namespace Tests\Feature;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MyCardsTest extends TestCase
{
	use RefreshDatabase;

	public function test_guests_are_sent_to_log_in()
	{
		$this->get(route('my-cards'))->assertRedirect(route('login'));
	}

	public function test_it_lists_my_active_cards_across_boards_soonest_due_first()
	{
		$user = User::factory()->create();
		$mine = Board::factory()->ownedBy($user)->create(['name' => 'Mine']);
		$shared = Board::factory()->ownedBy(User::factory()->create())->create(['name' => 'Shared']);
		$shared->members()->attach($user, ['role' => BoardRole::Member->value]);

		$todo = Stack::factory()->for($mine)->create(['name' => 'To do']);
		$doing = Stack::factory()->for($shared)->create(['name' => 'Doing']);
		$gone = Stack::factory()->for($mine)->create(['name' => 'Gone', 'archived_at' => now()]);

		$card = fn (Stack $stack, array $attributes) => tap(
			Card::factory()->for($stack)->create(['due_at' => null, 'completed_at' => null, ...$attributes]),
			fn (Card $card) => $card->members()->attach($user),
		);

		$card($todo, ['title' => 'No date']);
		$card($doing, ['title' => 'Later', 'due_at' => now()->addWeek()]);
		$card($todo, ['title' => 'Soon', 'due_at' => now()->addDay()]);
		$card($todo, ['title' => 'Done', 'due_at' => now()->addDays(2), 'completed_at' => now()]);
		$card($todo, ['title' => 'Archived', 'archived_at' => now()]);
		$card($gone, ['title' => 'In an archived list']);
		Card::factory()->for($todo)->create(['title' => 'Not mine']);

		$this->actingAs($user)
			->get(route('my-cards'))
			->assertOk()
			->assertInertia(fn (Assert $page) => $page
				->component('cards/Mine')
				->where('cards', fn ($cards) => collect($cards)->pluck('title')->all() === ['Soon', 'Done', 'Later', 'No date'])
				->where('cards.2.board.name', 'Shared')
				->where('cards.2.list', 'Doing')
				->where('cards.2.board.key', $shared->key)
			);
	}
}
