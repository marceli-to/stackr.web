<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Mail\AddedToProjectMail;
use App\Mail\ProjectInvitationMail;
use App\Models\Card;
use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectMembersTest extends TestCase
{
	use RefreshDatabase;

	private User $owner;

	private Project $project;

	protected function setUp(): void
	{
		parent::setUp();

		Mail::fake();

		$this->owner = User::factory()->create();
		$this->project = Project::factory()->ownedBy($this->owner)->create(['name' => 'Wallet']);
	}

	private function member(): User
	{
		$user = User::factory()->create();
		$this->project->members()->attach($user, ['role' => ProjectRole::Member->value]);

		return $user;
	}

	private function invite(string $email = 'new@example.com'): ProjectInvitation
	{
		return $this->project->invitations()->create([
			'email' => $email,
			'token' => ProjectInvitation::newToken(),
			'invited_by' => $this->owner->id,
		]);
	}

	public function test_the_board_lists_members_and_the_owners_pending_invitations()
	{
		$member = $this->member();
		$this->invite();

		$this->actingAs($this->owner)
			->get(route('projects.show', $this->project))
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
			->get(route('projects.show', $this->project))
			->assertInertia(fn (Assert $page) => $page
				->where('canManageMembers', false)
				->has('invitations', 0)
			);
	}

	public function test_inviting_someone_with_an_account_adds_them_right_away()
	{
		$user = User::factory()->create(['email' => 'sam@example.com']);

		$this->actingAs($this->owner)
			->post(route('projects.members.store', $this->project), ['email' => ' Sam@Example.com '])
			->assertSessionHasNoErrors();

		$this->assertSame(ProjectRole::Member, $this->project->roleOf($user));
		$this->assertDatabaseCount('project_invitations', 0);
		Mail::assertQueued(AddedToProjectMail::class, fn ($mail) => $mail->hasTo('sam@example.com'));
	}

	public function test_inviting_an_unknown_email_sends_an_invitation_link()
	{
		$this->actingAs($this->owner)
			->post(route('projects.members.store', $this->project), ['email' => 'New@Example.com'])
			->assertSessionHasNoErrors();

		$invitation = $this->project->invitations()->sole();

		$this->assertSame('new@example.com', $invitation->email);
		Mail::assertQueued(ProjectInvitationMail::class, fn ($mail) => $mail->hasTo('new@example.com')
			&& $mail->invitation->is($invitation));
	}

	public function test_inviting_the_same_email_again_renews_the_link()
	{
		$old = $this->invite()->token;

		$this->actingAs($this->owner)
			->post(route('projects.members.store', $this->project), ['email' => 'new@example.com']);

		$this->assertNotSame($old, $this->project->invitations()->sole()->token);
		Mail::assertQueuedCount(1);
	}

	public function test_members_already_on_the_project_cannot_be_invited_again()
	{
		$member = $this->member();

		$this->actingAs($this->owner)
			->post(route('projects.members.store', $this->project), ['email' => $member->email])
			->assertSessionHasErrors('email');

		Mail::assertNothingOutgoing();
	}

	public function test_only_the_owner_can_invite()
	{
		$this->actingAs($this->member())
			->post(route('projects.members.store', $this->project), ['email' => 'new@example.com'])
			->assertForbidden();

		$this->assertDatabaseCount('project_invitations', 0);
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

	public function test_opening_the_link_signed_in_joins_the_project_and_verifies_the_email()
	{
		$user = User::factory()->unverified()->create(['email' => 'new@example.com']);
		$invitation = $this->invite();

		$this->actingAs($user)
			->get(route('invitations.show', $invitation->token))
			->assertRedirect(route('projects.show', $this->project));

		$this->assertSame(ProjectRole::Member, $this->project->roleOf($user));
		$this->assertTrue($user->fresh()->hasVerifiedEmail());
		$this->assertModelMissing($invitation);
	}

	public function test_expired_and_unknown_links_do_nothing()
	{
		$user = User::factory()->create();
		$invitation = $this->invite();
		$invitation->forceFill(['updated_at' => now()->subDays(ProjectInvitation::VALID_DAYS + 1)])->saveQuietly();

		$this->actingAs($user)->get(route('invitations.show', $invitation->token))->assertRedirect(route('home'));
		$this->actingAs($user)->get(route('invitations.show', 'nope'))->assertRedirect(route('home'));

		$this->assertNull($this->project->roleOf($user));
	}

	public function test_the_owner_removes_a_member_and_their_card_assignments()
	{
		$member = $this->member();
		$card = Card::factory()->for(Stack::factory()->for($this->project))->create();
		$card->members()->attach($member);

		$this->actingAs($this->owner)
			->delete(route('projects.members.destroy', [$this->project, $member]))
			->assertRedirect();

		$this->assertNull($this->project->roleOf($member));
		$this->assertDatabaseMissing('card_user', ['user_id' => $member->id]);
	}

	public function test_members_can_leave_but_not_remove_others()
	{
		$member = $this->member();
		$other = $this->member();

		$this->actingAs($member)
			->delete(route('projects.members.destroy', [$this->project, $other]))
			->assertForbidden();

		$this->actingAs($member)
			->delete(route('projects.members.destroy', [$this->project, $member]))
			->assertRedirect(route('projects.index'));

		$this->assertNull($this->project->roleOf($member));
		$this->assertNotNull($this->project->roleOf($other));
	}

	public function test_the_owner_cannot_leave_or_be_removed()
	{
		$this->actingAs($this->owner)
			->delete(route('projects.members.destroy', [$this->project, $this->owner]))
			->assertForbidden();

		$this->assertSame(ProjectRole::Owner, $this->project->roleOf($this->owner));
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

		(new ProjectInvitationMail($invitation))
			->assertSeeInHtml('Wallet')
			->assertSeeInHtml(route('invitations.show', $invitation->token));
	}

	public function test_board_urls_use_the_project_key_and_old_id_urls_redirect()
	{
		$this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $this->project->key);
		$this->assertStringEndsWith('/projects/'.$this->project->key, route('projects.show', $this->project));

		$this->actingAs($this->owner)
			->get('/projects/'.$this->project->id.'?card=7')
			->assertRedirect(route('projects.show', [$this->project, 'card' => 7]));
		$this->actingAs(User::factory()->create())->get('/projects/'.$this->project->id)->assertForbidden();
	}

	public function test_the_owner_turns_the_join_link_on_renews_it_and_turns_it_off()
	{
		$this->actingAs($this->owner)->post(route('invite-link.store', $this->project));
		$first = $this->project->fresh()->invite_token;
		$this->assertNotNull($first);

		$this->actingAs($this->owner)
			->get(route('projects.show', $this->project))
			->assertInertia(fn (Assert $page) => $page->where('inviteLink', route('invite-link.show', $first)));

		$this->actingAs($this->owner)->post(route('invite-link.store', $this->project));
		$this->assertNotSame($first, $this->project->fresh()->invite_token);

		$this->actingAs($this->owner)->delete(route('invite-link.destroy', $this->project));
		$this->assertNull($this->project->fresh()->invite_token);
	}

	public function test_only_the_owner_manages_the_join_link_and_members_never_see_it()
	{
		$member = $this->member();
		$this->project->forceFill(['invite_token' => 'secret'])->save();

		$this->actingAs($member)->post(route('invite-link.store', $this->project))->assertForbidden();
		$this->actingAs($member)->delete(route('invite-link.destroy', $this->project))->assertForbidden();

		$this->actingAs($member)
			->get(route('projects.show', $this->project))
			->assertInertia(fn (Assert $page) => $page->where('inviteLink', null));
	}

	public function test_opening_the_join_link_signed_in_joins_the_project()
	{
		$user = User::factory()->create();
		$this->project->forceFill(['invite_token' => 'secret'])->save();

		$this->actingAs($user)
			->get(route('invite-link.show', 'secret'))
			->assertRedirect(route('projects.show', $this->project));

		$this->assertSame(ProjectRole::Member, $this->project->roleOf($user));

		// The owner opening their own link stays the owner.
		$this->actingAs($this->owner)->get(route('invite-link.show', 'secret'));
		$this->assertSame(ProjectRole::Owner, $this->project->roleOf($this->owner));
	}

	public function test_a_guest_opening_the_join_link_signs_up_first()
	{
		$this->project->forceFill(['invite_token' => 'secret'])->save();
		$link = route('invite-link.show', 'secret');

		$this->get($link)
			->assertRedirect(route('register'))
			->assertSessionHas('url.intended', $link);
	}

	public function test_a_turned_off_join_link_does_nothing()
	{
		$user = User::factory()->create();

		$this->actingAs($user)->get(route('invite-link.show', 'nope'))->assertRedirect(route('home'));

		$this->assertNull($this->project->roleOf($user));
	}
}
