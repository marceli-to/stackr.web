<?php

namespace Tests\Feature;

use App\Enums\BoardRole;
use App\Models\Board;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use App\Notifications\AssignedToCard;
use App\Notifications\CardDueSoon;
use App\Notifications\MentionedInComment;
use App\Support\Mentions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationTest extends TestCase
{
	use RefreshDatabase;

	private User $amy;

	private User $bob;

	private Board $board;

	private Card $card;

	protected function setUp(): void
	{
		parent::setUp();

		$this->amy = User::factory()->create(['name' => 'Amy Rossi']);
		$this->bob = User::factory()->create(['name' => 'Bob']);
		$this->board = Board::factory()->ownedBy($this->amy)->create(['name' => 'Website']);
		$this->board->members()->attach($this->bob, ['role' => BoardRole::Member]);
		$this->card = Card::factory()->for(Stack::factory()->for($this->board))->create(['title' => 'Brief', 'due_at' => null]);
	}

	public function test_mentions_match_whole_member_names()
	{
		$amy = User::factory()->make(['id' => 1, 'name' => 'Amy']);
		$amyRossi = User::factory()->make(['id' => 2, 'name' => 'Amy Rossi']);
		$members = collect([$amy, $amyRossi]);

		$this->assertSame([2], Mentions::in('Thanks @amy rossi!', $members)->pluck('id')->all());
		$this->assertEqualsCanonicalizing([1, 2], Mentions::in('@Amy and @Amy Rossi', $members)->pluck('id')->all());
		$this->assertSame([], Mentions::in('mail amy@Amy.com or @Amyx', $members)->pluck('id')->all());

		$this->assertSame(
			'<p>Hi <span class="mention">@Amy Rossi</span>, <a href="/@Amy">x</a></p>',
			Mentions::highlight('<p>Hi @Amy Rossi, <a href="/@Amy">x</a></p>', ['Amy', 'Amy Rossi']),
		);
	}

	public function test_a_mention_notifies_the_member_but_not_the_author()
	{
		Notification::fake();

		$this->actingAs($this->amy)
			->post(route('comments.store', $this->card), ['body' => '@Bob can you look? cc @Amy Rossi'])
			->assertSessionHasNoErrors();

		Notification::assertSentTo($this->bob, MentionedInComment::class);
		Notification::assertNotSentTo($this->amy, MentionedInComment::class);
	}

	public function test_editing_a_comment_only_notifies_new_mentions()
	{
		$this->actingAs($this->amy)->post(route('comments.store', $this->card), ['body' => '@Bob hi']);
		$comment = $this->card->comments()->sole();
		$carol = User::factory()->create(['name' => 'Carol']);
		$this->board->members()->attach($carol, ['role' => BoardRole::Member]);

		Notification::fake();

		$this->patch(route('comments.update', $comment), ['body' => '@Bob hi, and @Carol']);

		Notification::assertSentTo($carol, MentionedInComment::class);
		Notification::assertNotSentTo($this->bob, MentionedInComment::class);
	}

	public function test_comments_highlight_mentions()
	{
		$this->actingAs($this->amy)->post(route('comments.store', $this->card), ['body' => 'Over to @Bob']);

		$this->get(route('boards.show', [$this->board, 'card' => $this->card->id]))
			->assertInertia(fn (Assert $page) => $page
				->where('card.comments.0.body_html', "<p>Over to <span class=\"mention\">@Bob</span></p>\n")
			);
	}

	public function test_being_added_to_a_card_notifies_unless_you_did_it()
	{
		Notification::fake();

		$this->actingAs($this->amy)->post(route('cards.members.store', [$this->card, $this->bob]));
		$this->actingAs($this->amy)->post(route('cards.members.store', [$this->card, $this->amy]));

		Notification::assertSentTo($this->bob, AssignedToCard::class);
		Notification::assertNotSentTo($this->amy, AssignedToCard::class);
	}

	public function test_notifications_go_to_the_bell_and_to_email_when_wanted()
	{
		$this->bob->update(['email_notifications' => false]);

		$notification = new AssignedToCard($this->card, $this->amy);

		$this->assertSame(['database'], $notification->via($this->bob));
		$this->assertSame(['database', 'mail'], $notification->via($this->amy));

		$this->actingAs($this->amy)->post(route('cards.members.store', [$this->card, $this->bob]));

		$this->actingAs($this->bob)
			->get(route('boards.index'))
			->assertInertia(fn (Assert $page) => $page
				->where('notifications.unread', 1)
				->missing('notificationList')
				->reloadOnly('notificationList', fn (Assert $reload) => $reload
					->where('notificationList.0.card_title', 'Brief')
					->where('notificationList.0.actor.name', 'Amy Rossi')
					->where('notificationList.0.sentence', 'added you to')
					->where('notificationList.0.read', false)
				)
			);
	}

	public function test_the_email_links_to_the_card()
	{
		$this->actingAs($this->amy)->post(route('comments.store', $this->card), ['body' => '@Bob can you look?']);
		$comment = $this->card->comments()->sole();

		$html = (string) (new MentionedInComment($comment, $this->amy))->toMail($this->bob)->render();

		$this->assertStringContainsString('Amy Rossi mentioned you on “Brief” on Website.', $html);
		$this->assertStringContainsString('@Bob can you look?', $html);
		$this->assertStringContainsString(e(route('boards.show', [$this->board, 'card' => $this->card->id])), $html);
	}

	public function test_opening_a_notification_marks_it_read_and_opens_the_card()
	{
		$this->actingAs($this->amy)->post(route('cards.members.store', [$this->card, $this->bob]));
		$id = $this->bob->notifications()->sole()->id;

		$this->actingAs($this->bob)
			->post(route('notifications.open', $id))
			->assertRedirect(route('boards.show', [$this->board, 'card' => $this->card->id]));

		$this->assertSame(0, $this->bob->unreadNotifications()->count());
	}

	public function test_others_cant_open_your_notifications()
	{
		$this->actingAs($this->amy)->post(route('cards.members.store', [$this->card, $this->bob]));
		$id = $this->bob->notifications()->sole()->id;

		$this->actingAs($this->amy)->post(route('notifications.open', $id))->assertNotFound();
	}

	public function test_mark_all_read()
	{
		$this->actingAs($this->amy)->post(route('cards.members.store', [$this->card, $this->bob]));

		$this->actingAs($this->bob)->post(route('notifications.read-all'));

		$this->assertSame(0, $this->bob->unreadNotifications()->count());
	}

	public function test_members_are_reminded_once_when_a_card_is_due_within_a_day()
	{
		Notification::fake();
		$this->card->members()->attach([$this->amy->id, $this->bob->id]);
		$this->card->update(['due_at' => now()->addHours(5)]);
		$later = Card::factory()->for($this->card->stack)->create(['due_at' => now()->addDays(3)]);
		$later->members()->attach($this->amy);
		$done = Card::factory()->for($this->card->stack)->create(['due_at' => now()->addHour(), 'completed_at' => now()]);
		$done->members()->attach($this->amy);

		$this->artisan('app:send-due-reminders')->assertSuccessful();
		$this->artisan('app:send-due-reminders')->assertSuccessful();

		Notification::assertSentToTimes($this->amy, CardDueSoon::class, 1);
		Notification::assertSentToTimes($this->bob, CardDueSoon::class, 1);
		$this->assertNotNull($this->card->fresh()->due_reminded_at);
	}

	public function test_a_new_due_date_gets_a_new_reminder()
	{
		$this->card->update(['due_at' => now()->addHours(5), 'due_reminded_at' => now()]);

		$this->actingAs($this->amy)
			->patch(route('cards.update', $this->card), ['due_at' => now()->addHours(10)->toIso8601String()]);

		$this->assertNull($this->card->fresh()->due_reminded_at);
	}

	public function test_email_notifications_can_be_turned_off()
	{
		$this->actingAs($this->bob)
			->patch(route('profile.notifications'), ['email_notifications' => false])
			->assertSessionHasNoErrors();

		$this->assertFalse($this->bob->fresh()->email_notifications);
	}
}
