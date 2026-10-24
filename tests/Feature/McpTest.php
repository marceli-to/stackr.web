<?php

namespace Tests\Feature;

use App\Enums\LabelColor;
use App\Enums\ProjectRole;
use App\Mcp\Servers\StackrServer;
use App\Mcp\Tools\AddChecklistItemTool;
use App\Mcp\Tools\AddChecklistTool;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\AddLabelTool;
use App\Mcp\Tools\ArchiveCardTool;
use App\Mcp\Tools\AssignMemberTool;
use App\Mcp\Tools\CreateCardTool;
use App\Mcp\Tools\GetBoardTool;
use App\Mcp\Tools\GetCardTool;
use App\Mcp\Tools\MoveCardTool;
use App\Mcp\Tools\RemoveLabelTool;
use App\Mcp\Tools\TickChecklistItemTool;
use App\Mcp\Tools\UnassignMemberTool;
use App\Mcp\Tools\UpdateCardTool;
use App\Models\Card;
use App\Models\Project;
use App\Models\Stack;
use App\Models\User;
use App\Notifications\AssignedToCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class McpTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Project $project;

	private Stack $todo;

	private Stack $done;

	private Card $card;

	protected function setUp(): void
	{
		parent::setUp();

		$this->user = User::factory()->create(['name' => 'Amy']);
		$this->project = Project::factory()->ownedBy($this->user)->create(['name' => 'Website']);
		$this->todo = Stack::factory()->for($this->project)->create(['name' => 'To do']);
		$this->done = Stack::factory()->for($this->project)->create(['name' => 'Done']);
		$this->card = Card::factory()->for($this->todo)->create(['title' => 'Write the brief']);
	}

	/**
	 * Calls a tool over HTTP, the way an agent does.
	 *
	 * @param  array<string, mixed>  $arguments
	 */
	private function callTool(string $token, string $tool, array $arguments = []): TestResponse
	{
		return $this->withToken($token)->postJson('/mcp', [
			'jsonrpc' => '2.0',
			'id' => 1,
			'method' => 'tools/call',
			'params' => ['name' => $tool, 'arguments' => $arguments],
		]);
	}

	/**
	 * A board the user isn't on.
	 */
	private function strangersCard(): Card
	{
		$stranger = User::factory()->create();
		$project = Project::factory()->ownedBy($stranger)->create(['name' => 'Secret']);

		return Card::factory()->for(Stack::factory()->for($project)->create(['name' => 'Done']))->create();
	}

	public function test_agents_need_a_token()
	{
		$this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
			->assertUnauthorized();

		$this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], ['Authorization' => 'Bearer nope'])
			->assertUnauthorized();
	}

	public function test_a_revoked_token_stops_working()
	{
		$token = $this->user->createToken('Laptop');

		$this->callTool($token->plainTextToken, 'list_projects')->assertOk();

		$token->accessToken->delete();
		$this->app['auth']->forgetGuards();

		$this->callTool($token->plainTextToken, 'list_projects')->assertUnauthorized();
	}

	public function test_a_token_lists_only_its_users_projects()
	{
		$this->strangersCard();

		$this->callTool($this->user->createToken('Laptop')->plainTextToken, 'list_projects')
			->assertOk()
			->assertSee('Website')
			->assertDontSee('Secret');
	}

	public function test_what_an_agent_does_is_marked_via_agent()
	{
		$token = $this->user->createToken('Laptop')->plainTextToken;

		$this->callTool($token, 'move_card', ['card_id' => $this->card->id, 'list' => 'done'])
			->assertOk()
			->assertJsonPath('result.isError', false);
		$this->callTool($token, 'add_comment', ['card_id' => $this->card->id, 'body' => 'Shipped it.']);

		$this->assertTrue($this->card->refresh()->stack->is($this->done));

		$moved = $this->card->activities()->where('type', 'moved')->sole();
		$this->assertTrue($moved->via_agent);
		$this->assertTrue($moved->user->is($this->user));
		$this->assertTrue($this->card->comments()->sole()->via_agent);
	}

	public function test_the_web_app_is_not_marked_via_agent()
	{
		$this->actingAs($this->user)->post(route('comments.store', $this->card), ['body' => 'By hand.']);

		$this->assertFalse($this->card->comments()->sole()->via_agent);
	}

	public function test_get_board_shows_the_active_lists_and_cards()
	{
		Card::factory()->for($this->todo)->create(['title' => 'Old card', 'archived_at' => now()]);
		Card::factory()->for($this->todo)->create(['title' => 'Bug template', 'is_template' => true]);
		Stack::factory()->for($this->project)->create(['name' => 'Someday', 'archived_at' => now()]);

		StackrServer::actingAs($this->user)
			->tool(GetBoardTool::class, ['project' => $this->project->key])
			->assertOk()
			->assertSee(['To do', 'Done', 'Write the brief'])
			->assertDontSee(['Old card', 'Bug template', 'Someday']);

		StackrServer::actingAs($this->user)
			->tool(GetBoardTool::class, ['project' => 'website'])
			->assertOk()
			->assertSee('Write the brief');
	}

	public function test_other_boards_read_as_not_found()
	{
		$card = $this->strangersCard();

		StackrServer::actingAs($this->user)
			->tool(GetBoardTool::class, ['project' => $card->stack->project->key])
			->assertHasErrors(['no project called']);

		StackrServer::actingAs($this->user)
			->tool(GetCardTool::class, ['card_id' => $card->id])
			->assertHasErrors(["There's no card {$card->id} on your boards."]);

		StackrServer::actingAs($this->user)
			->tool(AddCommentTool::class, ['card_id' => $card->id, 'body' => 'Hi'])
			->assertHasErrors();

		StackrServer::actingAs($this->user)
			->tool(MoveCardTool::class, ['card_id' => $this->card->id, 'list' => (string) $card->stack->id])
			->assertHasErrors(['on your boards']);

		$this->assertSame(0, $card->comments()->count());
		$this->assertTrue($this->card->refresh()->stack->is($this->todo));
	}

	public function test_get_card_has_the_details()
	{
		$checklist = $this->card->checklists()->create(['title' => 'Steps']);
		$checklist->items()->create(['title' => 'Draft']);
		$this->card->update(['description' => 'For the **new** site']);

		StackrServer::actingAs($this->user)
			->tool(GetCardTool::class, ['card_id' => $this->card->id])
			->assertOk()
			->assertSee(['Write the brief', 'For the **new** site', 'Steps', 'Draft', 'To do']);
	}

	public function test_create_card_in_a_list_by_name()
	{
		StackrServer::actingAs($this->user)
			->tool(CreateCardTool::class, [
				'project' => $this->project->key,
				'list' => 'TO DO',
				'title' => 'Pick fonts',
				'description' => 'Two at most',
			])
			->assertOk()
			->assertSee('Pick fonts');

		$card = $this->todo->cards()->where('title', 'Pick fonts')->sole();
		$this->assertSame('Two at most', $card->description);
		$this->assertSame('created', $card->activities()->sole()->type);
	}

	public function test_update_card_completes_it()
	{
		StackrServer::actingAs($this->user)
			->tool(UpdateCardTool::class, ['card_id' => $this->card->id, 'completed' => true, 'title' => 'Brief'])
			->assertOk();

		$this->card->refresh();
		$this->assertNotNull($this->card->completed_at);
		$this->assertSame('Brief', $this->card->title);

		StackrServer::actingAs($this->user)
			->tool(UpdateCardTool::class, ['card_id' => $this->card->id, 'title' => ''])
			->assertHasErrors();
	}

	public function test_move_card_to_the_top_of_a_list()
	{
		$first = Card::factory()->for($this->done)->create();

		StackrServer::actingAs($this->user)
			->tool(MoveCardTool::class, ['card_id' => $this->card->id, 'list' => (string) $this->done->id, 'position' => 'top'])
			->assertOk();

		$this->assertSame([$this->card->id, $first->id], $this->done->cards()->pluck('id')->all());
	}

	public function test_move_card_asks_for_an_id_when_names_clash()
	{
		Stack::factory()->for($this->project)->create(['name' => 'done']);

		StackrServer::actingAs($this->user)
			->tool(MoveCardTool::class, ['card_id' => $this->card->id, 'list' => 'Done'])
			->assertHasErrors(['More than one list is called "Done". Use its id instead.']);
	}

	public function test_move_card_to_another_of_the_users_boards()
	{
		$other = Project::factory()->ownedBy($this->user)->create();
		$inbox = Stack::factory()->for($other)->create(['name' => 'Inbox']);

		StackrServer::actingAs($this->user)
			->tool(MoveCardTool::class, ['card_id' => $this->card->id, 'list' => (string) $inbox->id])
			->assertOk();

		$this->assertTrue($this->card->refresh()->stack->is($inbox));
	}

	public function test_checklists()
	{
		StackrServer::actingAs($this->user)
			->tool(AddChecklistTool::class, ['card_id' => $this->card->id, 'title' => 'Steps', 'items' => ['Draft', 'Review']])
			->assertOk();

		$checklist = $this->card->checklists()->sole();
		$this->assertSame(['Draft', 'Review'], $checklist->items()->pluck('title')->all());

		StackrServer::actingAs($this->user)
			->tool(AddChecklistItemTool::class, ['checklist_id' => $checklist->id, 'title' => 'Send'])
			->assertOk();

		$item = $checklist->items()->where('title', 'Draft')->sole();

		StackrServer::actingAs($this->user)
			->tool(TickChecklistItemTool::class, ['item_id' => $item->id])
			->assertOk();

		$this->assertNotNull($item->refresh()->completed_at);
		$this->assertSame(3, $checklist->items()->count());
	}

	public function test_checklists_on_other_boards_read_as_not_found()
	{
		$item = $this->strangersCard()->checklists()->create(['title' => 'Theirs'])->items()->create(['title' => 'Secret step']);

		StackrServer::actingAs($this->user)
			->tool(TickChecklistItemTool::class, ['item_id' => $item->id])
			->assertHasErrors(['on your boards']);

		StackrServer::actingAs($this->user)
			->tool(AddChecklistItemTool::class, ['checklist_id' => $item->checklist_id, 'title' => 'Mine'])
			->assertHasErrors(['on your boards']);

		$this->assertNull($item->refresh()->completed_at);
	}

	public function test_members_by_email_or_name()
	{
		Notification::fake();
		$bob = User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
		$this->project->members()->attach($bob, ['role' => ProjectRole::Member]);

		StackrServer::actingAs($this->user)
			->tool(AssignMemberTool::class, ['card_id' => $this->card->id, 'member' => 'BOB@example.com'])
			->assertOk();

		$this->assertTrue($this->card->members()->sole()->is($bob));
		Notification::assertSentTo($bob, AssignedToCard::class);

		StackrServer::actingAs($this->user)
			->tool(UnassignMemberTool::class, ['card_id' => $this->card->id, 'member' => 'bob'])
			->assertOk();

		$this->assertSame(0, $this->card->members()->count());
	}

	public function test_only_board_members_can_be_assigned()
	{
		$outsider = User::factory()->create(['name' => 'Eve']);

		StackrServer::actingAs($this->user)
			->tool(AssignMemberTool::class, ['card_id' => $this->card->id, 'member' => (string) $outsider->id])
			->assertHasErrors(["There's no member called \"{$outsider->id}\"."]);

		$this->assertSame(0, $this->card->members()->count());
	}

	public function test_labels_by_name()
	{
		$label = $this->project->labels()->create(['name' => 'Urgent', 'color' => LabelColor::Orange]);

		StackrServer::actingAs($this->user)
			->tool(AddLabelTool::class, ['card_id' => $this->card->id, 'label' => 'urgent'])
			->assertOk();

		$this->assertTrue($this->card->labels()->sole()->is($label));

		StackrServer::actingAs($this->user)
			->tool(RemoveLabelTool::class, ['card_id' => $this->card->id, 'label' => (string) $label->id])
			->assertOk();

		$this->assertSame(0, $this->card->labels()->count());
	}

	public function test_archive_card()
	{
		StackrServer::actingAs($this->user)
			->tool(ArchiveCardTool::class, ['card_id' => $this->card->id])
			->assertOk();

		$this->assertNotNull($this->card->refresh()->archived_at);

		StackrServer::actingAs($this->user)
			->tool(GetCardTool::class, ['card_id' => $this->card->id])
			->assertHasErrors();
	}
}
