<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Board;
use App\Models\BoardImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TrelloImportWizardTest extends TestCase
{
	use RefreshDatabase;

	private User $admin;

	protected function setUp(): void
	{
		parent::setUp();

		Storage::fake(Attachment::DISK);
		Notification::fake();

		$this->admin = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
		config(['app.admins' => ['ada@example.com']]);
	}

	public function test_the_wizard_lists_only_people_the_user_knows()
	{
		$friend = User::factory()->create(['name' => 'Friend']);
		User::factory()->create(['name' => 'Stranger']);
		$user = User::factory()->create(['name' => 'Bea']);
		Board::factory()->ownedBy($user)->hasAttached($friend, ['role' => 'member'], 'members')->create();

		$this->actingAs($user)->get(route('imports.trello'))
			->assertOk()
			->assertInertia(fn (AssertableInertia $page) => $page
				->component('imports/Trello')
				->where('canCreateUsers', false)
				->where('users', fn ($users) => collect($users)->pluck('name')->sort()->values()->all() === ['Bea', 'Friend']));
	}

	public function test_an_admin_imports_a_board_creates_an_account_and_fetches_the_files_one_by_one()
	{
		Http::fake(['trello.com/*' => Http::response('%PDF-1.4', 200)]);

		$response = $this->actingAs($this->admin)->post(route('imports.trello.store'), [
			'file' => $this->upload(),
			'name' => 'Imported',
			'people' => [
				'm1' => ['choice' => 'user', 'user_id' => $this->admin->id],
				'm2' => ['choice' => 'create', 'name' => 'Carla', 'email' => 'carla@example.com'],
			],
		]);

		$response->assertOk()->assertJsonPath('cards', 1)->assertJsonPath('import.total', 2)->assertJsonPath('created.0.email', 'carla@example.com');

		$carla = User::where('email', 'carla@example.com')->firstOrFail();
		$this->assertNotNull($carla->email_verified_at);
		$board = Board::where('name', 'Imported')->firstOrFail();
		$this->assertTrue($board->members()->whereKey($carla->id)->exists());
		$this->assertSame(0, Attachment::count());

		$import = BoardImport::findOrFail($response->json('import.id'));
		$url = route('imports.download', $import);

		$this->postJson($url, ['key' => 'k', 'token' => 't'])->assertOk()->assertJson(['done' => 1, 'total' => 2, 'finished' => false]);
		$this->postJson($url, ['key' => 'k', 'token' => 't'])->assertOk()->assertJson(['done' => 2, 'finished' => true]);

		$this->assertSame(2, Attachment::count());
		$this->assertSame($carla->id, Attachment::where('name', 'b.pdf')->firstOrFail()->user_id);
		$this->assertNull(BoardImport::find($import->id));
		Notification::assertNothingSent();
	}

	public function test_a_rejected_token_keeps_the_file_pending()
	{
		Http::fake(['trello.com/*' => Http::response('unauthorized', 401)]);
		$import = $this->importAsAdmin();

		$this->postJson(route('imports.download', $import), ['key' => 'k', 'token' => 'bad'])
			->assertUnprocessable()
			->assertJsonValidationErrors('token');

		$this->assertCount(2, $import->fresh()->pending);
	}

	public function test_a_missing_file_is_noted_and_skipped()
	{
		Http::fake(['trello.com/*' => Http::response('', 404)]);
		$import = $this->importAsAdmin();

		$this->postJson(route('imports.download', $import), ['key' => 'k', 'token' => 't'])
			->assertOk()
			->assertJsonPath('failed.0.name', 'a.pdf');
	}

	public function test_others_cannot_touch_an_import()
	{
		$import = $this->importAsAdmin();

		$this->actingAs(User::factory()->create())
			->postJson(route('imports.download', $import), ['key' => 'k', 'token' => 't'])
			->assertNotFound();
		$this->deleteJson(route('imports.destroy', $import))->assertNotFound();
	}

	public function test_only_admins_create_accounts_and_people_must_be_known()
	{
		$user = User::factory()->create();
		$stranger = User::factory()->create();

		$this->actingAs($user)->postJson(route('imports.trello.store'), [
			'file' => $this->upload(),
			'name' => 'Imported',
			'people' => [
				'm1' => ['choice' => 'user', 'user_id' => $stranger->id],
				'm2' => ['choice' => 'create', 'name' => 'Carla', 'email' => 'carla@example.com'],
			],
		])->assertJsonValidationErrors(['people.m1.user_id', 'people.m2.choice']);

		$this->assertSame(0, Board::count());
		$this->assertNull(User::where('email', 'carla@example.com')->first());
	}

	public function test_a_file_that_is_not_a_trello_export_is_refused()
	{
		$this->actingAs($this->admin)->postJson(route('imports.trello.store'), [
			'file' => UploadedFile::fake()->createWithContent('x.json', '{"hello": 1}'),
			'name' => 'Imported',
		])->assertJsonValidationErrors('file');
	}

	private function importAsAdmin(): BoardImport
	{
		$response = $this->actingAs($this->admin)->postJson(route('imports.trello.store'), [
			'file' => $this->upload(),
			'name' => 'Imported',
			'people' => ['m1' => ['choice' => 'user', 'user_id' => $this->admin->id], 'm2' => ['choice' => 'nobody']],
		])->assertOk();

		return BoardImport::findOrFail($response->json('import.id'));
	}

	private function upload(): UploadedFile
	{
		$attachment = fn (string $id, string $name, string $member) => [
			'id' => $id, 'name' => $name, 'fileName' => $name, 'isUpload' => true, 'mimeType' => 'application/pdf',
			'idMember' => $member, 'date' => '2025-04-16T10:30:00.000Z', 'url' => "https://trello.com/1/cards/x/attachments/{$id}/download/{$name}",
		];

		return UploadedFile::fake()->createWithContent('board.json', (string) json_encode([
			'name' => 'Old board',
			'members' => [
				['id' => 'm1', 'fullName' => 'Ada', 'username' => 'ada'],
				['id' => 'm2', 'fullName' => 'Carla', 'username' => 'carla'],
			],
			'labels' => [],
			'lists' => [['id' => 'l', 'name' => 'To do', 'pos' => 1, 'closed' => false]],
			'cards' => [[
				'id' => '67ff872d636253b635fdcf97', 'name' => 'Card', 'idList' => 'l', 'pos' => 1, 'desc' => '', 'closed' => false,
				'idLabels' => [], 'idMembers' => ['m1', 'm2'],
				'attachments' => [$attachment('a1', 'a.pdf', 'm1'), $attachment('a2', 'b.pdf', 'm2')],
			]],
			'checklists' => [],
			'actions' => [],
		]));
	}
}
