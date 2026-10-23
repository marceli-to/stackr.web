<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Card;
use App\Models\Project;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->ownedBy($this->user)->create();
    }

    /**
     * @return list<string>
     */
    private function stackNames(): array
    {
        return $this->project->stacks()->pluck('name')->all();
    }

    /**
     * @return list<string>
     */
    private function cardTitles(Stack $stack): array
    {
        return $stack->cards()->pluck('title')->all();
    }

    public function test_the_board_shows_lists_and_cards_in_order()
    {
        $todo = Stack::factory()->for($this->project)->create(['name' => 'To do']);
        $done = Stack::factory()->for($this->project)->create(['name' => 'Done']);
        Card::factory()->for($todo)->create(['title' => 'First']);
        Card::factory()->for($todo)->create(['title' => 'Second']);
        $todo->update(['position' => 'b0']);

        $this->actingAs($this->user)
            ->get(route('projects.show', $this->project))
            ->assertInertia(fn (Assert $page) => $page
                ->component('projects/Show')
                ->has('stacks', 2)
                ->where('stacks.0.id', $done->id)
                ->where('stacks.1.name', 'To do')
                ->where('stacks.1.cards.0.title', 'First')
                ->where('stacks.1.cards.1.title', 'Second')
            );
    }

    public function test_new_lists_are_added_at_the_end()
    {
        foreach (['To do', 'Doing', 'Done'] as $name) {
            $this->actingAs($this->user)
                ->post(route('stacks.store', $this->project), ['name' => $name])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(['To do', 'Doing', 'Done'], $this->stackNames());
    }

    public function test_a_list_needs_a_name()
    {
        $this->actingAs($this->user)
            ->post(route('stacks.store', $this->project), ['name' => ''])
            ->assertSessionHasErrors('name');
    }

    public function test_a_list_can_be_renamed()
    {
        $stack = Stack::factory()->for($this->project)->create(['name' => 'Old']);

        $this->actingAs($this->user)
            ->patch(route('stacks.update', $stack), ['name' => 'New'])
            ->assertSessionHasNoErrors();

        $this->assertSame('New', $stack->fresh()->name);
    }

    public function test_lists_can_be_reordered()
    {
        $a = Stack::factory()->for($this->project)->create(['name' => 'A']);
        Stack::factory()->for($this->project)->create(['name' => 'B']);
        Stack::factory()->for($this->project)->create(['name' => 'C']);

        $this->actingAs($this->user)
            ->patch(route('stacks.move', $a), ['index' => 2])
            ->assertSessionHasNoErrors();
        $this->assertSame(['B', 'C', 'A'], $this->stackNames());

        $this->actingAs($this->user)
            ->patch(route('stacks.move', $a), ['index' => 1]);
        $this->assertSame(['B', 'A', 'C'], $this->stackNames());

        $this->actingAs($this->user)
            ->patch(route('stacks.move', $a), ['index' => 0]);
        $this->assertSame(['A', 'B', 'C'], $this->stackNames());
    }

    public function test_deleting_a_list_deletes_its_cards()
    {
        $stack = Stack::factory()->for($this->project)->create();
        $card = Card::factory()->for($stack)->create();

        $this->actingAs($this->user)
            ->delete(route('stacks.destroy', $stack))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($stack);
        $this->assertModelMissing($card);
    }

    public function test_new_cards_are_added_to_the_bottom_of_a_list()
    {
        $stack = Stack::factory()->for($this->project)->create();

        foreach (['One', 'Two', 'Three'] as $title) {
            $this->actingAs($this->user)
                ->post(route('cards.store', $stack), ['title' => $title])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(['One', 'Two', 'Three'], $this->cardTitles($stack));
    }

    public function test_a_card_needs_a_title()
    {
        $stack = Stack::factory()->for($this->project)->create();

        $this->actingAs($this->user)
            ->post(route('cards.store', $stack), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    public function test_cards_can_be_reordered_within_a_list()
    {
        $stack = Stack::factory()->for($this->project)->create();
        $one = Card::factory()->for($stack)->create(['title' => 'One']);
        Card::factory()->for($stack)->create(['title' => 'Two']);
        Card::factory()->for($stack)->create(['title' => 'Three']);

        $this->actingAs($this->user)
            ->patch(route('cards.move', $one), ['index' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Two', 'One', 'Three'], $this->cardTitles($stack));
    }

    public function test_cards_can_be_moved_to_another_list()
    {
        $todo = Stack::factory()->for($this->project)->create();
        $done = Stack::factory()->for($this->project)->create();
        $card = Card::factory()->for($todo)->create(['title' => 'Ship it']);
        Card::factory()->for($done)->create(['title' => 'A']);
        Card::factory()->for($done)->create(['title' => 'B']);

        $this->actingAs($this->user)
            ->patch(route('cards.move', $card), ['stack_id' => $done->id, 'index' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame([], $this->cardTitles($todo));
        $this->assertSame(['A', 'Ship it', 'B'], $this->cardTitles($done));
    }

    public function test_cards_can_be_dropped_into_an_empty_list()
    {
        $todo = Stack::factory()->for($this->project)->create();
        $empty = Stack::factory()->for($this->project)->create();
        $card = Card::factory()->for($todo)->create();

        $this->actingAs($this->user)
            ->patch(route('cards.move', $card), ['stack_id' => $empty->id, 'index' => 0])
            ->assertSessionHasNoErrors();

        $this->assertSame($empty->id, $card->fresh()->stack_id);
    }

    public function test_cards_cannot_be_moved_into_another_projects_list()
    {
        $card = Card::factory()->for(Stack::factory()->for($this->project))->create();
        $foreign = Stack::factory()->for(Project::factory()->ownedBy($this->user))->create();

        $this->actingAs($this->user)
            ->patch(route('cards.move', $card), ['stack_id' => $foreign->id, 'index' => 0])
            ->assertSessionHasErrors('stack_id');

        $this->assertNotSame($foreign->id, $card->fresh()->stack_id);
    }

    public function test_members_can_edit_the_board()
    {
        $member = User::factory()->create();
        $this->project->members()->attach($member, ['role' => ProjectRole::Member->value]);

        $this->actingAs($member)
            ->post(route('stacks.store', $this->project), ['name' => 'From a member'])
            ->assertSessionHasNoErrors();

        $this->assertSame(['From a member'], $this->stackNames());
    }

    public function test_non_members_cannot_touch_the_board()
    {
        $stranger = User::factory()->create();
        $stack = Stack::factory()->for($this->project)->create(['name' => 'Mine']);
        $card = Card::factory()->for($stack)->create();

        $this->actingAs($stranger)->post(route('stacks.store', $this->project), ['name' => 'X'])->assertForbidden();
        $this->actingAs($stranger)->patch(route('stacks.update', $stack), ['name' => 'X'])->assertForbidden();
        $this->actingAs($stranger)->patch(route('stacks.move', $stack), ['index' => 0])->assertForbidden();
        $this->actingAs($stranger)->delete(route('stacks.destroy', $stack))->assertForbidden();
        $this->actingAs($stranger)->post(route('cards.store', $stack), ['title' => 'X'])->assertForbidden();
        $this->actingAs($stranger)->patch(route('cards.move', $card), ['index' => 0])->assertForbidden();

        $this->assertSame(['Mine'], $this->stackNames());
        $this->assertSame(1, $stack->cards()->count());
    }
}
