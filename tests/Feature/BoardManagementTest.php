<?php

namespace Tests\Feature;

use App\Enums\BoardColor;
use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoardManagementTest extends TestCase
{
	use RefreshDatabase;

	public function test_guests_cannot_see_boards()
	{
		$this->get(route('boards.index'))->assertRedirect(route('login'));
	}

	public function test_users_only_see_boards_they_belong_to()
	{
		$user = User::factory()->create();
		$mine = Board::factory()->ownedBy($user)->create(['name' => 'Mine']);
		$shared = Board::factory()->create(['name' => 'Shared']);
		$shared->members()->attach($user, ['role' => BoardRole::Member->value]);
		Board::factory()->ownedBy(User::factory()->create())->create(['name' => 'Someone else']);

		$stack = Stack::factory()->for($mine)->create();
		Card::factory()->count(2)->for($stack)->create();

		$this->actingAs($user)
			->get(route('boards.index'))
			->assertOk()
			->assertInertia(fn (Assert $page) => $page
				->component('boards/Index')
				->has('boards', 2)
				->where('boards.0.name', 'Mine')
				->where('boards.0.role', 'owner')
				->where('boards.0.stacks_count', 1)
				->where('boards.0.cards_count', 2)
				->has('boards.0.color')
				->where('boards.1.name', 'Shared')
				->where('boards.1.role', 'member')
			);
	}

	public function test_the_overview_lists_the_users_boards_alphabetically()
	{
		$user = User::factory()->create();
		Board::factory()->ownedBy($user)->create(['name' => 'Beta']);
		Board::factory()->ownedBy($user)->create(['name' => 'alpha']);
		Board::factory()->create(['name' => 'Not mine']);

		$this->actingAs($user)
			->get(route('boards.index'))
			->assertInertia(fn (Assert $page) => $page
				->has('boards', 2)
				->where('boards.0.name', 'alpha')
				->where('boards.1.name', 'Beta')
			);
	}

	public function test_a_user_can_create_several_boards_and_owns_them()
	{
		$user = User::factory()->create();

		foreach (['Website', 'App', 'Marketing'] as $name) {
			$this->actingAs($user)
				->post(route('boards.store'), ['name' => $name, 'description' => null])
				->assertSessionHasNoErrors();
		}

		$this->assertSame(3, $user->boards()->count());
		$this->assertSame(
			[BoardRole::Owner->value],
			$user->boards()->pluck('board_user.role')->unique()->values()->all(),
		);
	}

	public function test_creating_a_board_redirects_to_its_board()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)
			->post(route('boards.store'), ['name' => 'Website relaunch']);

		$board = Board::firstWhere('name', 'Website relaunch');
		$response->assertRedirect(route('boards.show', $board));
	}

	public function test_new_boards_cycle_through_the_colours()
	{
		$user = User::factory()->create();

		foreach (['A', 'B', 'C'] as $name) {
			$this->actingAs($user)->post(route('boards.store'), ['name' => $name]);
		}

		$this->assertSame(
			['cobalt', 'rust', 'moss'],
			$user->boards()->orderBy('boards.id')->get()->map(fn (Board $board) => $board->color->value)->all(),
		);
	}

	public function test_a_board_can_be_created_with_a_colour()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('boards.store'), ['name' => 'Plum one', 'color' => 'plum'])
			->assertSessionHasNoErrors();

		$this->assertSame(BoardColor::Plum, Board::firstWhere('name', 'Plum one')->color);
	}

	public function test_a_board_can_stand_on_a_gradient()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('boards.store'), ['name' => 'Dusky', 'color' => 'dusk'])
			->assertSessionHasNoErrors();

		$this->assertSame(BoardColor::Dusk, Board::firstWhere('name', 'Dusky')->color);
	}

	public function test_a_board_can_have_a_url()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('boards.store'), ['name' => 'Site', 'url' => 'https://example.com/staging'])
			->assertSessionHasNoErrors();

		$this->assertSame('https://example.com/staging', Board::firstWhere('name', 'Site')->url);
	}

	public function test_a_board_url_must_be_a_web_address()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('boards.store'), ['name' => 'Site', 'url' => 'javascript:alert(1)'])
			->assertSessionHasErrors('url');
	}

	public function test_a_board_colour_must_be_one_of_the_six()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('boards.store'), ['name' => 'Nope', 'color' => 'purple'])
			->assertSessionHasErrors('color');
	}

	public function test_a_board_needs_a_name()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('boards.store'), ['name' => ''])
			->assertSessionHasErrors('name');

		$this->assertSame(0, Board::count());
	}

	public function test_members_can_open_a_board()
	{
		$user = User::factory()->create();
		$board = Board::factory()->ownedBy($user)->create();

		$this->actingAs($user)
			->get(route('boards.show', $board))
			->assertOk()
			->assertInertia(fn (Assert $page) => $page
				->component('boards/Show')
				->where('board.id', $board->id)
			);
	}

	public function test_non_members_cannot_open_a_board()
	{
		$board = Board::factory()->ownedBy(User::factory()->create())->create();

		$this->actingAs(User::factory()->create())
			->get(route('boards.show', $board))
			->assertForbidden();
	}

	public function test_members_can_edit_a_board()
	{
		$user = User::factory()->create();
		$board = Board::factory()->create();
		$board->members()->attach($user, ['role' => BoardRole::Member->value]);

		$this->actingAs($user)
			->patch(route('boards.update', $board), ['name' => 'Renamed', 'description' => 'New text', 'color' => 'lagoon'])
			->assertSessionHasNoErrors();

		$board->refresh();
		$this->assertSame('Renamed', $board->name);
		$this->assertSame('New text', $board->description);
		$this->assertSame(BoardColor::Lagoon, $board->color);
	}

	public function test_non_members_cannot_edit_a_board()
	{
		$board = Board::factory()->ownedBy(User::factory()->create())->create(['name' => 'Original']);

		$this->actingAs(User::factory()->create())
			->patch(route('boards.update', $board), ['name' => 'Hijacked'])
			->assertForbidden();

		$this->assertSame('Original', $board->fresh()->name);
	}

	public function test_the_owner_can_delete_a_board_with_its_lists_and_cards()
	{
		$user = User::factory()->create();
		$board = Board::factory()->ownedBy($user)->create();
		$stack = Stack::factory()->for($board)->create();
		$card = Card::factory()->for($stack)->create();

		$this->actingAs($user)
			->delete(route('boards.destroy', $board))
			->assertRedirect(route('boards.index'));

		$this->assertModelMissing($board);
		$this->assertModelMissing($stack);
		$this->assertModelMissing($card);
	}

	public function test_members_cannot_delete_a_board()
	{
		$user = User::factory()->create();
		$board = Board::factory()->ownedBy(User::factory()->create())->create();
		$board->members()->attach($user, ['role' => BoardRole::Member->value]);

		$this->actingAs($user)
			->delete(route('boards.destroy', $board))
			->assertForbidden();

		$this->assertModelExists($board);
	}

	public function test_deleting_an_account_deletes_owned_boards_but_not_shared_ones()
	{
		$user = User::factory()->create();
		$owned = Board::factory()->ownedBy($user)->create();
		$shared = Board::factory()->ownedBy(User::factory()->create())->create();
		$shared->members()->attach($user, ['role' => BoardRole::Member->value]);

		$user->delete();

		$this->assertModelMissing($owned);
		$this->assertModelExists($shared);
		$this->assertSame(1, $shared->members()->count());
	}
}
