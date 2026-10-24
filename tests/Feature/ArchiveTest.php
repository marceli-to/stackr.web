<?php

namespace Tests\Feature;

use App\Models\Card;
use App\Models\Project;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArchiveTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Project $project;

	private Stack $stack;

	protected function setUp(): void
	{
		parent::setUp();

		$this->user = User::factory()->create();
		$this->project = Project::factory()->ownedBy($this->user)->create();
		$this->stack = Stack::factory()->for($this->project)->create(['name' => 'To do']);
	}

	public function test_an_archived_card_leaves_the_board_but_still_opens()
	{
		$card = Card::factory()->for($this->stack)->create(['title' => 'Old']);
		Card::factory()->for($this->stack)->create(['title' => 'Current']);

		$this->actingAs($this->user)
			->patch(route('cards.archive', $card))
			->assertSessionHasNoErrors();

		$this->assertNotNull($card->fresh()->archived_at);

		$this->actingAs($this->user)
			->get(route('projects.show', [$this->project, 'card' => $card->id]))
			->assertInertia(fn (Assert $page) => $page
				->has('stacks.0.cards', 1)
				->where('stacks.0.cards.0.title', 'Current')
				->where('card.id', $card->id)
				->where('card.archived', true)
				->missing('archive')
			);
	}

	public function test_the_archive_loads_on_request()
	{
		$card = Card::factory()->for($this->stack)->create(['title' => 'Old card', 'archived_at' => now()]);
		$list = Stack::factory()->for($this->project)->create(['name' => 'Old list', 'archived_at' => now()]);
		Card::factory()->for($list)->count(2)->create();

		$this->actingAs($this->user)
			->get(route('projects.show', $this->project))
			->assertInertia(fn (Assert $page) => $page
				->missing('archive')
				->reloadOnly('archive', fn (Assert $reload) => $reload
					->has('archive.cards', 1)
					->where('archive.cards.0.id', $card->id)
					->where('archive.cards.0.stack', 'To do')
					->where('archive.stacks.0.name', 'Old list')
					->where('archive.stacks.0.cards_count', 2)
				)
			);
	}

	public function test_a_restored_card_comes_back_at_its_old_place()
	{
		$first = Card::factory()->for($this->stack)->create(['title' => 'First']);
		$second = Card::factory()->for($this->stack)->create(['title' => 'Second']);
		Card::factory()->for($this->stack)->create(['title' => 'Third']);

		$this->actingAs($this->user)->patch(route('cards.archive', $second));
		$this->actingAs($this->user)->patch(route('cards.restore', $second))->assertSessionHasNoErrors();

		$this->assertNull($second->fresh()->archived_at);
		$this->assertSame(['First', 'Second', 'Third'], $this->stack->cards()->pluck('title')->all());
		$this->assertNotNull($first->fresh());
	}

	public function test_moves_count_only_the_cards_on_the_board()
	{
		$a = Card::factory()->for($this->stack)->create(['title' => 'A']);
		Card::factory()->for($this->stack)->create(['title' => 'Hidden', 'archived_at' => now()]);
		Card::factory()->for($this->stack)->create(['title' => 'B']);

		// On the board the list reads A, B; dropping A at index 1 puts it after B.
		$this->actingAs($this->user)
			->patch(route('cards.move', $a), ['index' => 1])
			->assertSessionHasNoErrors();

		$this->assertSame(['B', 'A'], $this->stack->cards()->active()->pluck('title')->all());
	}

	public function test_cards_cant_be_dropped_into_an_archived_list()
	{
		$card = Card::factory()->for($this->stack)->create();
		$archived = Stack::factory()->for($this->project)->create(['archived_at' => now()]);

		$this->actingAs($this->user)
			->patch(route('cards.move', $card), ['index' => 0, 'stack_id' => $archived->id])
			->assertSessionHasErrors('stack_id');
	}

	public function test_an_archived_list_takes_its_cards_off_the_board()
	{
		Card::factory()->for($this->stack)->create();
		$done = Stack::factory()->for($this->project)->create(['name' => 'Done']);

		$this->actingAs($this->user)
			->patch(route('stacks.archive', $this->stack))
			->assertSessionHasNoErrors();

		$this->actingAs($this->user)
			->get(route('projects.show', $this->project))
			->assertInertia(fn (Assert $page) => $page
				->has('stacks', 1)
				->where('stacks.0.id', $done->id)
			);

		$this->actingAs($this->user)
			->get(route('projects.index'))
			->assertInertia(fn (Assert $page) => $page
				->where('projects.0.stacks_count', 1)
				->where('projects.0.cards_count', 0)
			);

		$this->actingAs($this->user)->patch(route('stacks.restore', $this->stack));

		$this->assertNull($this->stack->fresh()->archived_at);
	}

	public function test_restoring_a_card_restores_its_archived_list()
	{
		$card = Card::factory()->for($this->stack)->create(['archived_at' => now()]);
		$this->stack->update(['archived_at' => now()]);

		$this->actingAs($this->user)->patch(route('cards.restore', $card));

		$this->assertNull($card->fresh()->archived_at);
		$this->assertNull($this->stack->fresh()->archived_at);
	}

	public function test_outsiders_cant_archive_or_restore()
	{
		$card = Card::factory()->for($this->stack)->create();
		$outsider = User::factory()->create();

		$this->actingAs($outsider)->patch(route('cards.archive', $card))->assertForbidden();
		$this->actingAs($outsider)->patch(route('cards.restore', $card))->assertForbidden();
		$this->actingAs($outsider)->patch(route('stacks.archive', $this->stack))->assertForbidden();
		$this->actingAs($outsider)->patch(route('stacks.restore', $this->stack))->assertForbidden();
	}
}
