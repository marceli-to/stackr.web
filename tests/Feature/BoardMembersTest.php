<?php

namespace Tests\Feature;

use App\Enums\BoardRole;
use App\Mail\AddedToBoardMail;
use App\Mail\BoardInvitationMail;
use App\Models\Board;
use App\Models\BoardInvitation;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoardMembersTest extends TestCase
{
	use RefreshDatabase;

	private User $owner;

	private Board $board;

	protected function setUp(): void
	{
		parent::setUp();

		Mail::fake();

		$this->owner = User::factory()->create();
		$this->board = Board::factory()->ownedBy($this->owner)->create(['name' => 'Wallet']);
	}

	private function member(): User
	{
		$user = User::factory()->create();
		$this->board->members()->attach($user, ['role' => BoardRole::Member->value]);

		return $user;
	}

	private function invite(string $email = 'new@example.com'): BoardInvitation
	{
		return $this->board->invitations()->create([
			'email' => $email,
			'token' => BoardInvitation::newToken(),
			'invited_by' => $this->owner->id,
		]);
	}

	public function test_the_board_lists_members_and_the_owners_pending_invitations()
	{
		$member = $this->member();
		$this->invite();

		$this->actingAs($this->owner)
			->get(route('boards.show', $this->board))
			->assertInertia(fn (Assert $page) => $page
				->where('canManageMembers', true)
				->has('members', 2)
				->where('members.0.id', $this->owner->id)
				->where('members.0.role', 'owner')
				->where('members.1.email', $member->email)
				->has('invitations', 1)
				->where('invitations.0.email', 'new@example.com')
				->missing('invitations.0.token')
			);

		$this->actingAs($member)
			->get(route('boards.show', $this->board))
			->assertInertia(fn (Assert $page) => $page
				->where('canManageMembers', false)
				->has('invitations', 0)
			);
	}

	public function test_inviting_someone_with_an_account_adds_them_right_away()
	{
		$user = User::factory()->create(['email' => 'sam@example.com']);

		$this->actingAs($this->owner)
			->post(route('boards.members.store', $this->board), ['email' => ' Sam@Example.com '])
			->assertSessionHasNoErrors();

		$this->assertSame(BoardRole::Member, $this->board->roleOf($user));
		$this->assertDatabaseCount('board_invitations', 0);
		Mail::assertQueued(AddedToBoardMail::class, fn ($mail) => $mail->hasTo('sam@example.com'));
	}

	public function test_inviting_an_unknown_email_sends_an_invitation_link()
	{
		$this->actingAs($this->owner)
			->post(route('boards.members.store', $this->board), ['email' => 'New@Example.com'])
			->assertSessionHasNoErrors();

		$invitation = $this->board->invitations()->sole();

		$this->assertSame('new@example.com', $invitation->email);
		Mail::assertQueued(BoardInvitationMail::class, fn ($mail) => $mail->hasTo('new@example.com')
			&& $mail->invitation->is($invitation));
	}

	public function test_inviting_the_same_email_again_renews_the_link()
	{
		$old = $this->invite()->token;

		$this->actingAs($this->owner)
			->post(route('boards.members.store', $this->board), ['email' => 'new@example.com']);

		$this->assertNotSame($old, $this->board->invitations()->sole()->token);
		Mail::assertQueuedCount(1);
	}

	public function test_members_already_on_the_board_cannot_be_invited_again()
	{
		$member = $this->member();

		$this->actingAs($this->owner)
			->post(route('boards.members.store', $this->board), ['email' => $member->email])
			->assertSessionHasErrors('email');

		Mail::assertNothingOutgoing();
	}

	public function test_only_the_owner_can_invite()
	{
		$this->actingAs($this->member())
			->post(route('boards.members.store', $this->board), ['email' => 'new@example.com'])
			->assertForbidden();

		$this->assertDatabaseCount('board_invitations', 0);
	}

	public function test_a_guest_opening_the_link_is_sent_to_sign_up_and_comes_back()
	{
		$invitation = $this->invite();
		$link = route('invitations.show', $invitation->token);

		$this->get($link)
			->assertRedirect(route('register', ['email' => 'new@example.com']))
			->assertSessionHas('url.intended', $link);
	}

	public function test_a_guest_with_an_account_is_sent_to_log_in()
	{
		User::factory()->create(['email' => 'new@example.com']);
		$invitation = $this->invite();

		$this->get(route('invitations.show', $invitation->token))
			->assertRedirect(route('login', ['email' => 'new@example.com']));
	}

	public function test_opening_the_link_signed_in_joins_the_board_and_verifies_the_email()
	{
		$user = User::factory()->unverified()->create(['email' => 'new@example.com']);
		$invitation = $this->invite();

		$this->actingAs($user)
			->get(route('invitations.show', $invitation->token))
			->assertRedirect(route('boards.show', $this->board));

		$this->assertSame(BoardRole::Member, $this->board->roleOf($user));
		$this->assertTrue($user->fresh()->hasVerifiedEmail());
		$this->assertModelMissing($invitation);
	}

	public function test_expired_and_unknown_links_do_nothing()
	{
		$user = User::factory()->create();
		$invitation = $this->invite();
		$invitation->forceFill(['updated_at' => now()->subDays(BoardInvitation::VALID_DAYS + 1)])->saveQuietly();

		$this->actingAs($user)->get(route('invitations.show', $invitation->token))->assertRedirect(route('home'));
		$this->actingAs($user)->get(route('invitations.show', 'nope'))->assertRedirect(route('home'));

		$this->assertNull($this->board->roleOf($user));
	}

	public function test_the_owner_removes_a_member_and_their_card_assignments()
	{
		$member = $this->member();
		$card = Card::factory()->for(Stack::factory()->for($this->board))->create();
		$card->members()->attach($member);

		$this->actingAs($this->owner)
			->delete(route('boards.members.destroy', [$this->board, $member]))
			->assertRedirect();

		$this->assertNull($this->board->roleOf($member));
		$this->assertDatabaseMissing('card_user', ['user_id' => $member->id]);
	}

	public function test_members_can_leave_but_not_remove_others()
	{
		$member = $this->member();
		$other = $this->member();

		$this->actingAs($member)
			->delete(route('boards.members.destroy', [$this->board, $other]))
			->assertForbidden();

		$this->actingAs($member)
			->delete(route('boards.members.destroy', [$this->board, $member]))
			->assertRedirect(route('boards.index'));

		$this->assertNull($this->board->roleOf($member));
		$this->assertNotNull($this->board->roleOf($other));
	}

	public function test_the_owner_cannot_leave_or_be_removed()
	{
		$this->actingAs($this->owner)
			->delete(route('boards.members.destroy', [$this->board, $this->owner]))
			->assertForbidden();

		$this->assertSame(BoardRole::Owner, $this->board->roleOf($this->owner));
	}

	public function test_the_owner_can_withdraw_an_invitation()
	{
		$invitation = $this->invite();

		$this->actingAs($this->member())
			->delete(route('invitations.destroy', $invitation))
			->assertForbidden();

		$this->actingAs($this->owner)
			->delete(route('invitations.destroy', $invitation))
			->assertRedirect();

		$this->assertModelMissing($invitation);
	}

	public function test_the_invitation_email_renders_with_its_link()
	{
		$invitation = $this->invite();

		(new BoardInvitationMail($invitation))
			->assertSeeInHtml('Wallet')
			->assertSeeInHtml(route('invitations.show', $invitation->token));
	}

	public function test_board_urls_use_the_board_key()
	{
		$this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $this->board->key);
		$this->assertStringEndsWith('/boards/'.$this->board->key, route('boards.show', $this->board));
	}

	public function test_the_owner_turns_the_join_link_on_renews_it_and_turns_it_off()
	{
		$this->actingAs($this->owner)->post(route('invite-link.store', $this->board));
		$first = $this->board->fresh()->invite_token;
		$this->assertNotNull($first);

		$this->actingAs($this->owner)
			->get(route('boards.show', $this->board))
			->assertInertia(fn (Assert $page) => $page->where('inviteLink', route('invite-link.show', $first)));

		$this->actingAs($this->owner)->post(route('invite-link.store', $this->board));
		$this->assertNotSame($first, $this->board->fresh()->invite_token);

		$this->actingAs($this->owner)->delete(route('invite-link.destroy', $this->board));
		$this->assertNull($this->board->fresh()->invite_token);
	}

	public function test_only_the_owner_manages_the_join_link_and_members_never_see_it()
	{
		$member = $this->member();
		$this->board->forceFill(['invite_token' => 'secret'])->save();

		$this->actingAs($member)->post(route('invite-link.store', $this->board))->assertForbidden();
		$this->actingAs($member)->delete(route('invite-link.destroy', $this->board))->assertForbidden();

		$this->actingAs($member)
			->get(route('boards.show', $this->board))
			->assertInertia(fn (Assert $page) => $page->where('inviteLink', null));
	}

	public function test_opening_the_join_link_signed_in_joins_the_board()
	{
		$user = User::factory()->create();
		$this->board->forceFill(['invite_token' => 'secret'])->save();

		$this->actingAs($user)
			->get(route('invite-link.show', 'secret'))
			->assertRedirect(route('boards.show', $this->board));

		$this->assertSame(BoardRole::Member, $this->board->roleOf($user));

		// The owner opening their own link stays the owner.
		$this->actingAs($this->owner)->get(route('invite-link.show', 'secret'));
		$this->assertSame(BoardRole::Owner, $this->board->roleOf($this->owner));
	}

	public function test_a_guest_opening_the_join_link_signs_up_first()
	{
		$this->board->forceFill(['invite_token' => 'secret'])->save();
		$link = route('invite-link.show', 'secret');

		$this->get($link)
			->assertRedirect(route('register'))
			->assertSessionHas('url.intended', $link);
	}

	public function test_a_turned_off_join_link_does_nothing()
	{
		$user = User::factory()->create();

		$this->actingAs($user)->get(route('invite-link.show', 'nope'))->assertRedirect(route('home'));

		$this->assertNull($this->board->roleOf($user));
	}
}
