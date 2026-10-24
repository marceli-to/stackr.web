<?php

namespace Tests\Feature;

use App\Enums\ProjectColor;
use App\Enums\ProjectRole;
use App\Models\Card;
use App\Models\Project;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProjectTest extends TestCase
{
	use RefreshDatabase;

	public function test_guests_cannot_see_projects()
	{
		$this->get(route('projects.index'))->assertRedirect(route('login'));
	}

	public function test_users_only_see_projects_they_belong_to()
	{
		$user = User::factory()->create();
		$mine = Project::factory()->ownedBy($user)->create(['name' => 'Mine']);
		$shared = Project::factory()->create(['name' => 'Shared']);
		$shared->members()->attach($user, ['role' => ProjectRole::Member->value]);
		Project::factory()->ownedBy(User::factory()->create())->create(['name' => 'Someone else']);

		$stack = Stack::factory()->for($mine)->create();
		Card::factory()->count(2)->for($stack)->create();

		$this->actingAs($user)
			->get(route('projects.index'))
			->assertOk()
			->assertInertia(fn (Assert $page) => $page
				->component('projects/Index')
				->has('projects', 2)
				->where('projects.0.name', 'Mine')
				->where('projects.0.role', 'owner')
				->where('projects.0.stacks_count', 1)
				->where('projects.0.cards_count', 2)
				->has('projects.0.color')
				->where('projects.1.name', 'Shared')
				->where('projects.1.role', 'member')
			);
	}

	public function test_the_overview_lists_the_users_projects_alphabetically()
	{
		$user = User::factory()->create();
		Project::factory()->ownedBy($user)->create(['name' => 'Beta']);
		Project::factory()->ownedBy($user)->create(['name' => 'alpha']);
		Project::factory()->create(['name' => 'Not mine']);

		$this->actingAs($user)
			->get(route('projects.index'))
			->assertInertia(fn (Assert $page) => $page
				->has('projects', 2)
				->where('projects.0.name', 'alpha')
				->where('projects.1.name', 'Beta')
			);
	}

	public function test_a_user_can_create_several_projects_and_owns_them()
	{
		$user = User::factory()->create();

		foreach (['Website', 'App', 'Marketing'] as $name) {
			$this->actingAs($user)
				->post(route('projects.store'), ['name' => $name, 'description' => null])
				->assertSessionHasNoErrors();
		}

		$this->assertSame(3, $user->projects()->count());
		$this->assertSame(
			[ProjectRole::Owner->value],
			$user->projects()->pluck('project_user.role')->unique()->values()->all(),
		);
	}

	public function test_creating_a_project_redirects_to_its_board()
	{
		$user = User::factory()->create();

		$response = $this->actingAs($user)
			->post(route('projects.store'), ['name' => 'Website relaunch']);

		$project = Project::firstWhere('name', 'Website relaunch');
		$response->assertRedirect(route('projects.show', $project));
	}

	public function test_new_projects_cycle_through_the_colours()
	{
		$user = User::factory()->create();

		foreach (['A', 'B', 'C'] as $name) {
			$this->actingAs($user)->post(route('projects.store'), ['name' => $name]);
		}

		$this->assertSame(
			['cobalt', 'rust', 'moss'],
			$user->projects()->orderBy('projects.id')->get()->map(fn (Project $project) => $project->color->value)->all(),
		);
	}

	public function test_a_project_can_be_created_with_a_colour()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('projects.store'), ['name' => 'Plum one', 'color' => 'plum'])
			->assertSessionHasNoErrors();

		$this->assertSame(ProjectColor::Plum, Project::firstWhere('name', 'Plum one')->color);
	}

	public function test_a_project_can_have_a_url()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('projects.store'), ['name' => 'Site', 'url' => 'https://example.com/staging'])
			->assertSessionHasNoErrors();

		$this->assertSame('https://example.com/staging', Project::firstWhere('name', 'Site')->url);
	}

	public function test_a_project_url_must_be_a_web_address()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('projects.store'), ['name' => 'Site', 'url' => 'javascript:alert(1)'])
			->assertSessionHasErrors('url');
	}

	public function test_a_project_colour_must_be_one_of_the_six()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('projects.store'), ['name' => 'Nope', 'color' => 'purple'])
			->assertSessionHasErrors('color');
	}

	public function test_a_project_needs_a_name()
	{
		$user = User::factory()->create();

		$this->actingAs($user)
			->post(route('projects.store'), ['name' => ''])
			->assertSessionHasErrors('name');

		$this->assertSame(0, Project::count());
	}

	public function test_members_can_open_a_project()
	{
		$user = User::factory()->create();
		$project = Project::factory()->ownedBy($user)->create();

		$this->actingAs($user)
			->get(route('projects.show', $project))
			->assertOk()
			->assertInertia(fn (Assert $page) => $page
				->component('projects/Show')
				->where('project.id', $project->id)
			);
	}

	public function test_non_members_cannot_open_a_project()
	{
		$project = Project::factory()->ownedBy(User::factory()->create())->create();

		$this->actingAs(User::factory()->create())
			->get(route('projects.show', $project))
			->assertForbidden();
	}

	public function test_members_can_edit_a_project()
	{
		$user = User::factory()->create();
		$project = Project::factory()->create();
		$project->members()->attach($user, ['role' => ProjectRole::Member->value]);

		$this->actingAs($user)
			->patch(route('projects.update', $project), ['name' => 'Renamed', 'description' => 'New text', 'color' => 'lagoon'])
			->assertSessionHasNoErrors();

		$project->refresh();
		$this->assertSame('Renamed', $project->name);
		$this->assertSame('New text', $project->description);
		$this->assertSame(ProjectColor::Lagoon, $project->color);
	}

	public function test_non_members_cannot_edit_a_project()
	{
		$project = Project::factory()->ownedBy(User::factory()->create())->create(['name' => 'Original']);

		$this->actingAs(User::factory()->create())
			->patch(route('projects.update', $project), ['name' => 'Hijacked'])
			->assertForbidden();

		$this->assertSame('Original', $project->fresh()->name);
	}

	public function test_the_owner_can_delete_a_project_with_its_lists_and_cards()
	{
		$user = User::factory()->create();
		$project = Project::factory()->ownedBy($user)->create();
		$stack = Stack::factory()->for($project)->create();
		$card = Card::factory()->for($stack)->create();

		$this->actingAs($user)
			->delete(route('projects.destroy', $project))
			->assertRedirect(route('projects.index'));

		$this->assertModelMissing($project);
		$this->assertModelMissing($stack);
		$this->assertModelMissing($card);
	}

	public function test_members_cannot_delete_a_project()
	{
		$user = User::factory()->create();
		$project = Project::factory()->ownedBy(User::factory()->create())->create();
		$project->members()->attach($user, ['role' => ProjectRole::Member->value]);

		$this->actingAs($user)
			->delete(route('projects.destroy', $project))
			->assertForbidden();

		$this->assertModelExists($project);
	}

	public function test_deleting_an_account_deletes_owned_projects_but_not_shared_ones()
	{
		$user = User::factory()->create();
		$owned = Project::factory()->ownedBy($user)->create();
		$shared = Project::factory()->ownedBy(User::factory()->create())->create();
		$shared->members()->attach($user, ['role' => ProjectRole::Member->value]);

		$user->delete();

		$this->assertModelMissing($owned);
		$this->assertModelExists($shared);
		$this->assertSame(1, $shared->members()->count());
	}
}
