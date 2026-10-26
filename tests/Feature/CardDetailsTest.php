<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CardDetailsTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Board $board;

	private Card $card;

	protected function setUp(): void
	{
		parent::setUp();

		Storage::fake(Attachment::DISK);

		$this->user = User::factory()->create();
		$this->board = Board::factory()->ownedBy($this->user)->create();
		$stack = Stack::factory()->for($this->board)->create(['name' => 'To do']);
		$this->card = Card::factory()->for($stack)->create(['title' => 'Write copy', 'description' => null, 'due_at' => null]);
	}

	public function test_the_board_opens_a_card_from_the_url()
	{
		$this->card->update(['description' => 'Some **bold** text']);

		$this->actingAs($this->user)
			->get(route('boards.show', ['board' => $this->board, 'card' => $this->card->id]))
			->assertInertia(fn (Assert $page) => $page
				->where('card.id', $this->card->id)
				->where('card.stack.name', 'To do')
				->where('card.description_html', "<p>Some <strong>bold</strong> text</p>\n")
			);
	}

	public function test_a_card_from_another_board_does_not_open()
	{
		$other = Card::factory()->create();

		$this->actingAs($this->user)
			->get(route('boards.show', ['board' => $this->board, 'card' => $other->id]))
			->assertInertia(fn (Assert $page) => $page->where('card', null));
	}

	public function test_markdown_drops_raw_html_and_unsafe_links()
	{
		$this->card->update(['description' => "<script>alert(1)</script>\n\n[x](javascript:alert(1))"]);

		$this->actingAs($this->user)
			->get(route('boards.show', ['board' => $this->board, 'card' => $this->card->id]))
			->assertInertia(fn (Assert $page) => $page->where('card.description_html', fn (string $html) => ! str_contains($html, '<script') && ! str_contains($html, 'javascript:')));
	}

	public function test_a_card_can_be_edited_field_by_field()
	{
		$this->actingAs($this->user)
			->patch(route('cards.update', $this->card), ['title' => 'Write the copy'])
			->assertSessionHasNoErrors();

		$this->actingAs($this->user)
			->patch(route('cards.update', $this->card), ['start_at' => '2026-11-01', 'due_at' => '2026-11-05'])
			->assertSessionHasNoErrors();

		$this->actingAs($this->user)->patch(route('cards.update', $this->card), ['completed' => true]);

		$card = $this->card->fresh();
		$this->assertSame('Write the copy', $card->title);
		$this->assertSame('2026-11-05', $card->due_at?->toDateString());
		$this->assertNotNull($card->completed_at);

		$this->actingAs($this->user)->patch(route('cards.update', $this->card), ['completed' => false]);
		$this->assertNull($this->card->fresh()->completed_at);
	}

	public function test_the_due_date_cannot_be_before_the_start()
	{
		$this->actingAs($this->user)
			->patch(route('cards.update', $this->card), ['start_at' => '2026-11-05', 'due_at' => '2026-11-01'])
			->assertSessionHasErrors('due_at');
	}

	public function test_a_card_can_be_deleted_with_its_files()
	{
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->image('a.png')]]);

		$directory = Attachment::directoryFor($this->board->id, $this->card->id);
		$this->assertNotEmpty(Storage::disk(Attachment::DISK)->files($directory));

		$this->actingAs($this->user)
			->delete(route('cards.destroy', $this->card))
			->assertRedirect(route('boards.show', $this->board));

		$this->assertModelMissing($this->card);
		$this->assertEmpty(Storage::disk(Attachment::DISK)->allFiles($directory));
	}

	public function test_labels_belong_to_the_board_and_go_on_cards()
	{
		$this->actingAs($this->user)
			->post(route('labels.store', $this->board), ['name' => 'Urgent', 'color' => 'red'])
			->assertSessionHasNoErrors();

		$label = $this->board->labels()->firstOrFail();

		$this->actingAs($this->user)->post(route('cards.labels.store', [$this->card, $label]));
		$this->assertSame([$label->id], $this->card->labels()->pluck('labels.id')->all());

		$this->actingAs($this->user)->delete(route('cards.labels.destroy', [$this->card, $label]));
		$this->assertSame(0, $this->card->labels()->count());
	}

	public function test_a_label_from_another_board_cannot_go_on_a_card()
	{
		$foreign = Label::forceCreate(['board_id' => Board::factory()->create()->id, 'color' => 'blue']);

		$this->actingAs($this->user)
			->post(route('cards.labels.store', [$this->card, $foreign]))
			->assertNotFound();
	}

	public function test_only_board_members_can_be_assigned()
	{
		$this->actingAs($this->user)->post(route('cards.members.store', [$this->card, $this->user]));
		$this->assertSame([$this->user->id], $this->card->members()->pluck('users.id')->all());

		$this->actingAs($this->user)
			->post(route('cards.members.store', [$this->card, User::factory()->create()]))
			->assertNotFound();
	}

	public function test_checklists_and_items()
	{
		$this->actingAs($this->user)->post(route('checklists.store', $this->card), ['title' => 'Launch']);
		$checklist = $this->card->checklists()->firstOrFail();

		$this->actingAs($this->user)->post(route('checklist-items.store', $checklist), ['title' => 'DNS']);
		$item = $checklist->items()->firstOrFail();

		$this->actingAs($this->user)->patch(route('checklist-items.update', $item), ['done' => true]);
		$this->assertNotNull($item->fresh()->completed_at);

		$this->actingAs($this->user)
			->get(route('boards.show', ['board' => $this->board, 'card' => $this->card->id]))
			->assertInertia(fn (Assert $page) => $page
				->where('stacks.0.cards.0.checklist', ['done' => 1, 'total' => 1])
				->where('card.checklists.0.items.0.title', 'DNS')
				->where('card.checklists.0.items.0.done', true)
			);

		$this->actingAs($this->user)->delete(route('checklists.destroy', $checklist));
		$this->assertSame(0, Checklist::count());
	}

	public function test_uploading_an_image_makes_it_the_cover_and_members_can_view_it()
	{
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [
				UploadedFile::fake()->image('photo.jpg', 800, 600),
				UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf'),
			]])
			->assertSessionHasNoErrors();

		$photo = Attachment::firstWhere('name', 'photo.jpg');
		$this->assertSame($photo->id, $this->card->fresh()->cover_attachment_id);
		$this->assertSame(800, $photo->width);

		$this->actingAs($this->user)->get(route('attachments.show', $photo))->assertOk();
		$this->actingAs(User::factory()->create())->get(route('attachments.show', $photo))->assertForbidden();
	}

	public function test_deleting_the_cover_image_clears_the_cover_and_the_file()
	{
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->image('photo.jpg')]]);
		$photo = Attachment::firstOrFail();

		$this->actingAs($this->user)->delete(route('attachments.destroy', $photo));

		$this->assertNull($this->card->fresh()->cover_attachment_id);
		Storage::disk(Attachment::DISK)->assertMissing($photo->path);
	}

	public function test_a_colour_cover_and_an_image_cover_replace_each_other()
	{
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->image('a.png')]]);
		$image = $this->card->attachments()->sole();

		$this->actingAs($this->user)
			->patch(route('cards.update', $this->card), ['cover_color' => 'sky'])
			->assertSessionHasNoErrors();
		$this->assertSame('sky', $this->card->fresh()->cover_color?->value);
		$this->assertNull($this->card->fresh()->cover_attachment_id);

		// A new image doesn't push a colour cover aside...
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->image('b.png')]]);
		$this->assertNull($this->card->fresh()->cover_attachment_id);

		// ...but picking one does.
		$this->actingAs($this->user)
			->patch(route('cards.update', $this->card), ['cover_attachment_id' => $image->id]);
		$this->assertNull($this->card->fresh()->cover_color);
		$this->assertSame($image->id, $this->card->fresh()->cover_attachment_id);

		$this->actingAs($this->user)
			->patch(route('cards.update', $this->card), ['cover_color' => 'pink'])
			->assertSessionHasErrors('cover_color');
	}

	public function test_only_images_of_this_card_can_be_the_cover()
	{
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf')]]);
		$pdf = Attachment::firstOrFail();

		$this->actingAs($this->user)
			->patch(route('cards.update', $this->card), ['cover_attachment_id' => $pdf->id])
			->assertSessionHasErrors('cover_attachment_id');
	}

	public function test_comments_can_only_be_changed_by_their_author()
	{
		$this->actingAs($this->user)->post(route('comments.store', $this->card), ['body' => 'Looks good']);
		$comment = Comment::firstOrFail();

		$other = User::factory()->create();
		$this->board->members()->attach($other, ['role' => 'member']);

		$this->actingAs($other)
			->patch(route('comments.update', $comment), ['body' => 'Changed'])
			->assertForbidden();

		$this->actingAs($this->user)->patch(route('comments.update', $comment), ['body' => 'Looks great']);
		$this->assertSame('Looks great', $comment->fresh()->body);

		$this->actingAs($this->user)->delete(route('comments.destroy', $comment));
		$this->assertModelMissing($comment);
	}

	public function test_members_can_react_to_comments()
	{
		$this->actingAs($this->user)->post(route('comments.store', $this->card), ['body' => 'Looks good']);
		$comment = Comment::firstOrFail();

		$other = User::factory()->create(['name' => 'Ada']);
		$this->board->members()->attach($other, ['role' => 'member']);

		$this->actingAs($other)->post(route('reactions.toggle', $comment), ['emoji' => '🎉']);
		$this->actingAs($this->user)->post(route('reactions.toggle', $comment), ['emoji' => '🎉']);
		$this->actingAs($this->user)->post(route('reactions.toggle', $comment), ['emoji' => '👍']);

		$this->actingAs($this->user)
			->get(route('boards.show', ['board' => $this->board, 'card' => $this->card->id]))
			->assertInertia(fn (Assert $page) => $page
				->has('card.comments.0.reactions', 2)
				->where('card.comments.0.reactions.0.emoji', '🎉')
				->where('card.comments.0.reactions.0.count', 2)
				->where('card.comments.0.reactions.0.mine', true)
				->where('card.comments.0.reactions.0.names', ['Ada', $this->user->name])
				->where('card.comments.0.reactions.1.emoji', '👍')
			);

		// The same emoji again takes it back.
		$this->actingAs($this->user)->post(route('reactions.toggle', $comment), ['emoji' => '👍']);
		$this->assertSame(['🎉', '🎉'], $comment->reactions()->pluck('emoji')->all());

		$this->actingAs($this->user)
			->post(route('reactions.toggle', $comment), ['emoji' => '💩'])
			->assertSessionHasErrors('emoji');

		$stranger = User::factory()->create();
		$this->actingAs($stranger)->post(route('reactions.toggle', $comment), ['emoji' => '👍'])->assertForbidden();
	}

	public function test_non_members_cannot_touch_a_card()
	{
		$stranger = User::factory()->create();

		$this->actingAs($stranger)->patch(route('cards.update', $this->card), ['title' => 'x'])->assertForbidden();
		$this->actingAs($stranger)->delete(route('cards.destroy', $this->card))->assertForbidden();
		$this->actingAs($stranger)->post(route('comments.store', $this->card), ['body' => 'x'])->assertForbidden();
		$this->actingAs($stranger)->post(route('checklists.store', $this->card), ['title' => 'x'])->assertForbidden();
		$this->actingAs($stranger)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->image('a.png')]])
			->assertForbidden();
	}

	public function test_deleting_a_list_removes_its_cards_files()
	{
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [UploadedFile::fake()->image('a.png')]]);

		$this->actingAs($this->user)->delete(route('stacks.destroy', $this->card->stack));

		$this->assertEmpty(Storage::disk(Attachment::DISK)->allFiles("attachments/{$this->board->id}"));
	}
}
