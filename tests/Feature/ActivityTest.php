<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Attachment;
use App\Models\Card;
use App\Models\Project;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Project $project;

	private Stack $todo;

	protected function setUp(): void
	{
		parent::setUp();

		$this->user = User::factory()->create(['name' => 'Amy']);
		$this->project = Project::factory()->ownedBy($this->user)->create();
		$this->todo = Stack::factory()->for($this->project)->create(['name' => 'To do']);
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
		$doing = Stack::factory()->for($this->project)->create(['name' => 'Doing']);
		$bob = User::factory()->create(['name' => 'Bob']);
		$this->project->members()->attach($bob, ['role' => ProjectRole::Member]);

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

		$this->get(route('projects.show', [$this->project, 'card' => $card->id]))
			->assertInertia(fn (Assert $page) => $page
				->has('card.activities', 2)
				->where('card.activities.0.type', 'archived')
				->where('card.activities.0.user.name', 'Amy')
				->where('card.activities.1.type', 'created')
				->where('card.activities.1.data.stack', 'To do')
			);
	}
}
