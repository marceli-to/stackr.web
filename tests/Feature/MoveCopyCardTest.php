<?php

namespace Tests\Feature;

use App\Enums\BoardRole;
use App\Enums\LabelColor;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MoveCopyCardTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Board $board;

	private Stack $stack;

	private Card $card;

	protected function setUp(): void
	{
		parent::setUp();

		Storage::fake(Attachment::DISK);

		$this->user = User::factory()->create();
		$this->board = Board::factory()->ownedBy($this->user)->create();
		$this->stack = Stack::factory()->for($this->board)->create(['name' => 'To do']);
		$this->card = Card::factory()->for($this->stack)->create(['title' => 'Brief']);
	}

	private function otherBoard(): Stack
	{
		$board = Board::factory()->ownedBy($this->user)->create(['name' => 'Other']);

		return Stack::factory()->for($board)->create(['name' => 'Inbox']);
	}

	public function test_the_destinations_list_the_users_boards_and_lists()
	{
		$inbox = $this->otherBoard();
		Card::factory()->for($inbox)->count(2)->create();

		$this->actingAs($this->user)
			->get(route('boards.show', [$this->board, 'card' => $this->card->id]))
			->assertInertia(fn (Assert $page) => $page
				->missing('destinations')
				->where('card.index', 0)
				->reloadOnly('destinations', fn (Assert $reload) => $reload
					->has('destinations', 2)
					->where('destinations.0.name', 'Other')
					->where('destinations.0.stacks.0.name', 'Inbox')
					->where('destinations.0.stacks.0.cards_count', 2)
				)
			);
	}

	public function test_a_card_moves_to_another_board_with_its_labels_members_and_files()
	{
		$inbox = $this->otherBoard();
		$both = User::factory()->create();
		$onlyHere = User::factory()->create();
		$this->board->members()->attach([$both->id, $onlyHere->id], ['role' => BoardRole::Member]);
		$inbox->board->members()->attach($both->id, ['role' => BoardRole::Member]);

		$label = $this->board->labels()->create(['name' => 'Bug', 'color' => LabelColor::Red]);
		$inbox->board->labels()->create(['name' => 'Bug', 'color' => LabelColor::Red]);
		$unnamed = $this->board->labels()->create(['name' => null, 'color' => LabelColor::Blue]);
		$this->card->labels()->attach([$label->id, $unnamed->id]);
		$this->card->members()->attach([$both->id, $onlyHere->id]);

		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->image('a.png')]]);
		$old = $this->card->attachments()->sole()->path;

		$this->actingAs($this->user)
			->patch(route('cards.move', $this->card), ['stack_id' => $inbox->id, 'index' => 0])
			->assertRedirect(route('boards.show', $this->board));

		$card = $this->card->fresh();
		$this->assertSame($inbox->id, $card->stack_id);
		$this->assertEqualsCanonicalizing(
			$inbox->board->labels()->pluck('id')->all(),
			$card->labels()->pluck('labels.id')->all(),
		);
		$this->assertSame(2, $inbox->board->labels()->count());
		$this->assertSame([$both->id], $card->members()->pluck('users.id')->all());

		$new = $card->attachments()->sole()->path;
		$this->assertStringStartsWith("attachments/{$inbox->board_id}/{$card->id}/", $new);
		Storage::disk(Attachment::DISK)->assertExists($new);
		Storage::disk(Attachment::DISK)->assertMissing($old);
	}

	public function test_a_card_cant_move_to_a_board_the_user_isnt_on()
	{
		$foreign = Stack::factory()->for(Board::factory()->create())->create();

		$this->actingAs($this->user)
			->patch(route('cards.move', $this->card), ['stack_id' => $foreign->id, 'index' => 0])
			->assertSessionHasErrors('stack_id');
	}

	public function test_a_copy_keeps_what_was_picked()
	{
		$label = $this->board->labels()->create(['name' => 'Bug', 'color' => LabelColor::Red]);
		$this->card->labels()->attach($label);
		$this->card->members()->attach($this->user);
		$this->card->update(['description' => 'Notes', 'due_at' => now()->addDay(), 'completed_at' => now()]);
		$checklist = $this->card->checklists()->create(['title' => 'Steps']);
		$checklist->items()->create(['title' => 'One', 'completed_at' => now()]);
		$checklist->items()->create(['title' => 'Two']);
		$this->card->comments()->create(['body' => 'Hi', 'user_id' => $this->user->id]);
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->image('cover.png')]]);

		$this->actingAs($this->user)
			->post(route('cards.copy', $this->card), [
				'stack_id' => $this->stack->id,
				'index' => 0,
				'title' => 'Brief (copy)',
				'keep' => ['checklists', 'labels', 'attachments'],
			])
			->assertSessionHasNoErrors();

		$copy = Card::query()->where('title', 'Brief (copy)')->sole();
		$this->assertSame(['Brief (copy)', 'Brief'], $this->stack->cards()->pluck('title')->all());
		$this->assertSame('Notes', $copy->description);
		$this->assertNotNull($copy->due_at);
		$this->assertNull($copy->completed_at);
		$this->assertSame([$label->id], $copy->labels()->pluck('labels.id')->all());
		$this->assertSame(0, $copy->members()->count());
		$this->assertSame(0, $copy->comments()->count());
		$this->assertSame(['One', 'Two'], $copy->checklists()->sole()->items()->pluck('title')->all());
		$this->assertNotNull($copy->checklists()->sole()->items()->first()?->completed_at);

		$attachment = $copy->attachments()->sole();
		$this->assertSame($attachment->id, $copy->cover_attachment_id);
		$this->assertNotSame($this->card->attachments()->sole()->path, $attachment->path);
		Storage::disk(Attachment::DISK)->assertExists($attachment->path);
	}

	public function test_a_copy_on_another_board_gets_that_boards_labels()
	{
		$inbox = $this->otherBoard();
		$label = $this->board->labels()->create(['name' => 'Bug', 'color' => LabelColor::Red]);
		$this->card->labels()->attach($label);

		$this->actingAs($this->user)
			->post(route('cards.copy', $this->card), [
				'stack_id' => $inbox->id,
				'index' => 0,
				'title' => 'Brief',
				'keep' => ['labels'],
			])
			->assertSessionHasNoErrors();

		$copy = $inbox->cards()->sole();
		$this->assertSame($inbox->board->labels()->sole()->id, $copy->labels()->sole()->id);
		$this->assertSame([$label->id], $this->card->labels()->pluck('labels.id')->all());
	}

	public function test_a_copy_needs_a_title_and_a_list()
	{
		$this->actingAs($this->user)
			->post(route('cards.copy', $this->card), ['index' => 0, 'title' => ''])
			->assertSessionHasErrors(['title', 'stack_id']);
	}
}
