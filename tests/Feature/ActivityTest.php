<?php

namespace Tests\Feature;

use App\Enums\BoardRole;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use App\Support\BoardFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Board $board;

	private Stack $todo;

	protected function setUp(): void
	{
		parent::setUp();

		$this->user = User::factory()->create(['name' => 'Amy']);
		$this->board = Board::factory()->ownedBy($this->user)->create();
		$this->todo = Stack::factory()->for($this->board)->create(['name' => 'To do']);
	}

	/**
	 * @return list<string>
	 */
	private function types(Card $card): array
	{
		return $card->activities()->pluck('type')->reverse()->values()->all();
	}

	public function test_a_cards_life_is_logged()
	{
		Storage::fake(Attachment::DISK);
		$doing = Stack::factory()->for($this->board)->create(['name' => 'Doing']);
		$bob = User::factory()->create(['name' => 'Bob']);
		$this->board->members()->attach($bob, ['role' => BoardRole::Member]);

		$this->actingAs($this->user)->post(route('cards.store', $this->todo), ['title' => 'Brief']);
		$card = $this->todo->cards()->sole();

		$this->patch(route('cards.move', $card), ['index' => 0]);
		$this->patch(route('cards.move', $card), ['index' => 0, 'stack_id' => $doing->id]);
		$this->post(route('cards.members.store', [$card, $bob]));
		$this->post(route('cards.members.store', [$card, $bob]));
		$this->patch(route('cards.update', $card), ['due_at' => now()->addDay()->toIso8601String()]);
		$this->patch(route('cards.update', $card), ['completed' => true]);
		$this->post(route('attachments.store', $card), ['files' => [UploadedFile::fake()->create('brief.pdf', 10)]]);
		$this->post(route('checklists.store', $card), ['title' => 'Steps']);
		$this->patch(route('cards.archive', $card));
		$this->patch(route('cards.restore', $card));
		$this->delete(route('cards.members.destroy', [$card, $bob]));

		$this->assertSame([
			'created', 'moved', 'member_added', 'due_set', 'completed',
			'attached', 'checklist_added', 'archived', 'restored', 'member_removed',
		], $this->types($card));

		$moved = $card->activities()->where('type', 'moved')->sole();
		$this->assertSame(['from' => 'To do', 'to' => 'Doing'], $moved->data);
		$this->assertSame($this->user->id, $moved->user_id);
	}

	public function test_the_card_dialog_shows_the_log_newest_first()
	{
		$this->actingAs($this->user)->post(route('cards.store', $this->todo), ['title' => 'Brief']);
		$card = $this->todo->cards()->sole();
		$this->patch(route('cards.archive', $card));

		$this->get(route('boards.show', [$this->board, 'card' => $card->id]))
			->assertInertia(fn (Assert $page) => $page
				->has('card.activities', 2)
				->where('card.activities.0.type', 'archived')
				->where('card.activities.0.user.name', 'Amy')
				->where('card.activities.1.type', 'created')
				->where('card.activities.1.data.stack', 'To do')
			);
	}

	public function test_the_board_feed_mixes_activity_and_comments_newest_first()
	{
		$this->travelTo(now()->subHour());
		$this->actingAs($this->user)->post(route('cards.store', $this->todo), ['title' => 'Brief']);
		$card = Card::firstOrFail();
		$this->travelBack();

		$this->post(route('comments.store', $card), ['body' => "Looks   good,\n\nship it"]);
		$this->patch(route('cards.archive', $card));

		// Another board's cards stay out.
		Card::factory()->create()->activities()->forceCreate(['type' => 'created', 'user_id' => $this->user->id]);

		$this->get(route('boards.show', $this->board))
			->assertInertia(fn (Assert $page) => $page
				->missing('feed')
				->reloadOnly('feed', fn (Assert $reload) => $reload
					->has('feed.entries', 3)
					->where('feed.more', false)
					->where('feed.entries.0.kind', 'activity')
					->where('feed.entries.0.type', 'archived')
					->where('feed.entries.0.card.title', 'Brief')
					->where('feed.entries.0.card.archived', true)
					->where('feed.entries.1.kind', 'comment')
					->where('feed.entries.1.snippet', 'Looks good, ship it')
					->where('feed.entries.1.user.name', 'Amy')
					->where('feed.entries.2.type', 'created')
				)
			);
	}

	public function test_the_board_feed_comes_in_pages()
	{
		$card = Card::factory()->for($this->todo)->create();

		foreach (range(1, 3) as $minutes) {
			$this->travelTo(now()->addMinutes($minutes));
			$card->activities()->forceCreate(['type' => 'completed', 'user_id' => $this->user->id]);
			$this->travel(30)->seconds();
			$card->comments()->forceCreate(['body' => "Note {$minutes}", 'user_id' => $this->user->id]);
		}

		$page = BoardFeed::latest($this->board, 4);
		$this->assertCount(4, $page['entries']);
		$this->assertTrue($page['more']);
		$this->assertSame('Note 3', $page['entries']->first()?->getAttribute('body'));

		$this->assertFalse(BoardFeed::latest($this->board, 6)['more']);
	}

	public function test_only_members_see_the_feed()
	{
		$this->actingAs(User::factory()->create())
			->get(route('boards.show', $this->board))
			->assertForbidden();
	}
}
