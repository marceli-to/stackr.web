<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TemplateTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Board $board;

	private Stack $stack;

	protected function setUp(): void
	{
		parent::setUp();

		$this->user = User::factory()->create();
		$this->board = Board::factory()->ownedBy($this->user)->create();
		$this->stack = Stack::factory()->for($this->board)->create();
	}

	public function test_a_card_becomes_a_template_and_starts_new_cards()
	{
		$template = Card::factory()->for($this->stack)->create(['title' => 'Bug report', 'description' => 'Steps:']);
		$template->members()->attach($this->user);
		$checklist = $template->checklists()->create(['title' => 'Triage']);
		$checklist->items()->create(['title' => 'Reproduce']);

		$this->actingAs($this->user)
			->patch(route('cards.update', $template), ['is_template' => true])
			->assertSessionHasNoErrors();
		$this->assertTrue($template->fresh()->is_template);

		$other = Stack::factory()->for($this->board)->create();
		Card::factory()->for($other)->create();

		$response = $this->actingAs($this->user)->post(route('cards.from-template', [$other, $template]));

		$card = $other->cards()->where('title', 'Bug report')->sole();
		$response->assertRedirect(route('boards.show', [$this->board, 'card' => $card->id]));
		$this->assertFalse($card->is_template);
		$this->assertSame('Steps:', $card->description);
		$this->assertSame(1, $card->members()->count());
		$this->assertSame(['Reproduce'], $card->checklists()->sole()->items()->pluck('title')->all());
		$this->assertSame('from_template', $card->activities()->sole()->type);
		$this->assertSame($card->id, $other->cards()->get()->last()?->id);
	}

	public function test_only_templates_of_the_same_board_can_start_cards()
	{
		$plain = Card::factory()->for($this->stack)->create();
		$foreign = Card::factory()->for(Stack::factory()->for(Board::factory()->ownedBy($this->user)))->create(['is_template' => true]);

		$this->actingAs($this->user)->post(route('cards.from-template', [$this->stack, $plain]))->assertNotFound();
		$this->actingAs($this->user)->post(route('cards.from-template', [$this->stack, $foreign]))->assertNotFound();
	}

	public function test_a_checklist_can_copy_the_items_of_another_one_unticked()
	{
		$source = Card::factory()->for($this->stack)->create(['title' => 'Release']);
		$list = $source->checklists()->create(['title' => 'Go live']);
		$list->items()->create(['title' => 'Backup', 'completed_at' => now()]);
		$list->items()->create(['title' => 'Deploy']);
		$card = Card::factory()->for($this->stack)->create();

		$this->actingAs($this->user)
			->get(route('boards.show', [$this->board, 'card' => $card->id]))
			->assertInertia(fn (Assert $page) => $page
				->missing('checklists')
				->reloadOnly('checklists', fn (Assert $reload) => $reload
					->where('checklists.0.title', 'Go live')
					->where('checklists.0.card', 'Release')
					->where('checklists.0.items_count', 2)
				)
			);

		$this->actingAs($this->user)
			->post(route('checklists.store', $card), ['title' => 'Go live', 'copy_from' => $list->id])
			->assertSessionHasNoErrors();

		$copy = $card->checklists()->sole();
		$this->assertSame(['Backup', 'Deploy'], $copy->items()->pluck('title')->all());
		$this->assertSame(0, $copy->items()->whereNotNull('completed_at')->count());
	}

	public function test_checklists_cant_be_copied_from_other_boards()
	{
		$foreign = Card::factory()->for(Stack::factory()->for(Board::factory()->ownedBy($this->user)))->create();
		$list = $foreign->checklists()->create(['title' => 'Secret']);
		$card = Card::factory()->for($this->stack)->create();

		$this->actingAs($this->user)
			->post(route('checklists.store', $card), ['title' => 'X', 'copy_from' => $list->id])
			->assertSessionHasErrors('copy_from');
	}
}
