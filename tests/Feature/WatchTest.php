<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Card;
use App\Models\Project;
use App\Models\Stack;
use App\Models\User;
use App\Notifications\AssignedToCard;
use App\Notifications\CardUpdated;
use App\Notifications\MentionedInComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WatchTest extends TestCase
{
	use RefreshDatabase;

	private User $amy;

	private User $bob;

	private Project $project;

	private Stack $todo;

	private Card $card;

	protected function setUp(): void
	{
		parent::setUp();

		$this->amy = User::factory()->create(['name' => 'Amy']);
		$this->bob = User::factory()->create(['name' => 'Bob']);
		$this->project = Project::factory()->ownedBy($this->amy)->create();
		$this->project->members()->attach($this->bob, ['role' => ProjectRole::Member]);
		$this->todo = Stack::factory()->for($this->project)->create(['name' => 'To do']);
		$this->card = Card::factory()->for($this->todo)->create(['title' => 'Brief']);
	}

	public function test_watching_a_card_shows_on_the_board_and_in_the_dialog()
	{
		$this->actingAs($this->bob)->post(route('cards.watch', $this->card))->assertRedirect();

		$this->actingAs($this->bob)
			->get(route('projects.show', [$this->project, 'card' => $this->card->id]))
			->assertInertia(fn (Assert $page) => $page
				->where('stacks.0.cards.0.watching', true)
				->where('card.watching', true)
				->where('watching', false)
			);

		$this->actingAs($this->amy)
			->get(route('projects.show', $this->project))
			->assertInertia(fn (Assert $page) => $page->where('stacks.0.cards.0.watching', false));

		$this->actingAs($this->bob)->delete(route('cards.unwatch', $this->card));
		$this->assertSame(0, $this->card->watchers()->count());
	}

	public function test_card_watchers_hear_about_changes_but_not_their_own()
	{
		Notification::fake();
		$this->actingAs($this->bob)->post(route('cards.watch', $this->card));
		$doing = Stack::factory()->for($this->project)->create(['name' => 'Doing']);

		$this->actingAs($this->amy)->patch(route('cards.move', $this->card), ['index' => 0, 'stack_id' => $doing->id]);

		Notification::assertSentTo($this->bob, CardUpdated::class, fn (CardUpdated $n) => $n->what === 'Moved from To do to Doing');
		Notification::assertNotSentTo($this->amy, CardUpdated::class);

		Notification::fake();
		$this->actingAs($this->bob)->patch(route('cards.update', $this->card), ['completed' => true]);
		Notification::assertNothingSent();
	}

	public function test_board_watchers_and_card_members_hear_about_comments()
	{
		$carol = User::factory()->create(['name' => 'Carol']);
		$this->project->members()->attach($carol, ['role' => ProjectRole::Member]);
		$this->actingAs($carol)->post(route('projects.watch', $this->project));
		$this->card->members()->attach($this->bob);

		Notification::fake();
		$this->actingAs($this->amy)->post(route('comments.store', $this->card), ['body' => 'Done, @Bob']);

		Notification::assertSentTo($carol, CardUpdated::class, fn (CardUpdated $n) => $n->what === 'Commented: Done, @Bob');
		// Bob is mentioned, so he gets the mention, not the update too.
		Notification::assertSentTo($this->bob, MentionedInComment::class);
		Notification::assertNotSentTo($this->bob, CardUpdated::class);
	}

	public function test_someone_added_to_a_card_gets_the_assignment_not_an_update()
	{
		$this->actingAs($this->bob)->post(route('projects.watch', $this->project));

		Notification::fake();
		$this->actingAs($this->amy)->post(route('cards.members.store', [$this->card, $this->bob]));

		Notification::assertSentTo($this->bob, AssignedToCard::class);
		Notification::assertNotSentTo($this->bob, CardUpdated::class);
	}

	public function test_updates_stay_in_the_bell()
	{
		$this->assertSame(['database'], (new CardUpdated($this->card, $this->amy, 'x'))->via($this->bob));
	}

	public function test_leaving_the_board_stops_the_watching()
	{
		$this->actingAs($this->bob)->post(route('projects.watch', $this->project));
		$this->actingAs($this->bob)->post(route('cards.watch', $this->card));

		$this->actingAs($this->amy)->delete(route('projects.members.destroy', [$this->project, $this->bob]));

		$this->assertSame(0, $this->project->watchers()->count());
		$this->assertSame(0, $this->card->watchers()->count());
	}

	public function test_outsiders_cant_watch()
	{
		$outsider = User::factory()->create();

		$this->actingAs($outsider)->post(route('cards.watch', $this->card))->assertForbidden();
		$this->actingAs($outsider)->post(route('projects.watch', $this->project))->assertForbidden();
	}
}
