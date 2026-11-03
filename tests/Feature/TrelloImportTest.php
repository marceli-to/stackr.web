<?php

namespace Tests\Feature;

use App\Enums\LabelColor;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrelloImportTest extends TestCase
{
	use RefreshDatabase;

	private User $owner;

	private string $file;

	private ?string $stored = null;

	protected function setUp(): void
	{
		parent::setUp();

		Storage::fake(Attachment::DISK);
		Notification::fake();

		$this->owner = User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com']);
		$this->file = tempnam(sys_get_temp_dir(), 'trello-').'.json';
		file_put_contents($this->file, json_encode($this->export()));
	}

	protected function tearDown(): void
	{
		@unlink($this->file);

		if ($this->stored !== null) {
			File::deleteDirectory(dirname($this->stored));
		}

		parent::tearDown();
	}

	public function test_a_trello_export_becomes_a_board_with_its_cards_comments_and_files()
	{
		$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
		Http::fake(['trello.com/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);
		$bob = User::factory()->create(['name' => 'Bob Builder']);

		$this->artisan('app:import-trello', [
			'file' => $this->file,
			'--owner' => 'ada@example.com',
			'--key' => 'k',
			'--token' => 't',
			'--no-interaction' => true,
		])->assertSuccessful();

		$board = Board::where('name', 'Old board')->firstOrFail();
		$this->assertSame(['Doing', 'Done'], $board->stacks()->pluck('name')->all());
		$this->assertTrue($board->members()->whereKey($bob->id)->exists());

		$done = $board->stacks()->where('name', 'Done')->firstOrFail();
		$this->assertSame(['First', 'Second'], $done->cards()->pluck('title')->all());

		$card = $done->cards()->where('title', 'First')->firstOrFail();
		$this->assertSame('2025-04-16', $card->created_at->toDateString());
		$this->assertSame(LabelColor::Red, $card->labels()->firstOrFail()->color);
		$this->assertSame([$bob->id], $card->members()->pluck('users.id')->all());
		$this->assertSame(['Step one'], $card->checklists()->firstOrFail()->items()->whereNotNull('completed_at')->pluck('title')->all());

		$comments = $card->comments()->reorder()->oldest('id')->get();
		$this->assertSame($bob->id, $comments[0]->user_id);
		$this->assertSame('Looks good', $comments[0]->body);
		$this->assertSame($this->owner->id, $comments[1]->user_id);
		$this->assertSame('**Carla Ghost:** Agreed', $comments[1]->body);

		$attachment = $card->attachments()->firstOrFail();
		$this->assertSame('shot.png', $attachment->name);
		$this->assertSame($bob->id, $attachment->user_id);
		$this->assertSame(1, $attachment->width);
		Storage::disk(Attachment::DISK)->assertExists($attachment->path);
		$this->assertSame($attachment->id, $card->cover_attachment_id);

		$this->assertStringContainsString('[Spec](https://example.com/spec)', (string) $card->description);
		Http::assertSent(fn ($request) => str_contains($request->header('Authorization')[0] ?? '', 'oauth_token="t"'));
		Notification::assertNothingSent();
	}

	public function test_the_command_asks_for_the_file_board_owner_and_people_and_creates_verified_accounts()
	{
		Http::fake();
		File::ensureDirectoryExists(storage_path('app/private/imports-test'));
		$this->stored = storage_path('app/private/imports-test/board.json');
		copy($this->file, $this->stored);
		$bob = User::factory()->create(['name' => 'Bob Builder']);

		$this->artisan('app:import-trello')
			->expectsQuestion('Which Trello export?', $this->stored)
			->expectsQuestion('Name of the new board', 'Renamed board')
			->expectsQuestion('Who owns the board?', (string) $this->owner->id)
			->expectsQuestion('Trello: Bob Builder (@bob) is…', (string) $bob->id)
			->expectsQuestion('Trello: Carla Ghost (@carla) is…', '__create')
			->expectsQuestion('Name', 'Carla Ghost')
			->expectsQuestion('Email', 'carla@example.com')
			->expectsQuestion('Trello API key (empty: skip the files)', '')
			->expectsOutputToContain('Created Carla Ghost <carla@example.com> with the password')
			->assertSuccessful();

		$carla = User::where('email', 'carla@example.com')->firstOrFail();
		$this->assertNotNull($carla->email_verified_at);

		$board = Board::where('name', 'Renamed board')->firstOrFail();
		$this->assertTrue($board->members()->whereKey($carla->id)->exists());
		$this->assertTrue($board->members()->whereKey($bob->id)->exists());

		$comment = $board->stacks()->where('name', 'Done')->firstOrFail()->cards()->where('title', 'First')->firstOrFail()->comments()->where('body', 'Agreed')->firstOrFail();
		$this->assertSame($carla->id, $comment->user_id);
		Notification::assertNothingSent();
	}

	public function test_without_a_key_the_files_are_skipped_with_a_warning()
	{
		Http::fake();

		$this->artisan('app:import-trello', ['file' => $this->file, '--owner' => 'ada@example.com', '--no-interaction' => true])
			->expectsOutputToContain('not downloaded')
			->assertSuccessful();

		Http::assertNothingSent();
		$this->assertSame(0, Attachment::count());
	}

	public function test_it_refuses_an_unknown_owner()
	{
		$this->artisan('app:import-trello', ['file' => $this->file, '--owner' => 'nobody@example.com', '--no-interaction' => true])->assertFailed();

		$this->assertSame(0, Board::count());
	}

	/**
	 * @return array<string, mixed>
	 */
	private function export(): array
	{
		return [
			'name' => 'Old board',
			'desc' => '',
			'members' => [
				['id' => 'm1', 'fullName' => 'Bob Builder', 'username' => 'bob'],
				['id' => 'm2', 'fullName' => 'Carla Ghost', 'username' => 'carla'],
			],
			'labels' => [['id' => 'l1', 'name' => 'Urgent', 'color' => 'pink_dark']],
			'lists' => [
				['id' => 'done', 'name' => 'Done', 'pos' => 2, 'closed' => false],
				['id' => 'doing', 'name' => 'Doing', 'pos' => 1, 'closed' => false],
			],
			'cards' => [
				['id' => '67ff9000aaaaaaaaaaaaaaaa', 'name' => 'Second', 'idList' => 'done', 'pos' => 20, 'desc' => '', 'closed' => false, 'idLabels' => [], 'idMembers' => [], 'attachments' => []],
				[
					'id' => '67ff872d636253b635fdcf97', 'name' => 'First', 'idList' => 'done', 'pos' => 10,
					'desc' => 'Some *markdown*', 'closed' => false, 'dateLastActivity' => '2025-05-01T10:00:00.000Z',
					'idLabels' => ['l1'], 'idMembers' => ['m1', 'm2'], 'idAttachmentCover' => 'a1',
					'attachments' => [
						['id' => 'a1', 'name' => 'shot.png', 'fileName' => 'shot.png', 'isUpload' => true, 'mimeType' => 'image/png', 'idMember' => 'm1', 'date' => '2025-04-16T10:30:00.000Z', 'url' => 'https://trello.com/1/cards/x/attachments/a1/download/shot.png'],
						['id' => 'a2', 'name' => 'Spec', 'isUpload' => false, 'url' => 'https://example.com/spec', 'date' => '2025-04-16T10:31:00.000Z'],
					],
				],
			],
			'checklists' => [
				['id' => 'c1', 'idCard' => '67ff872d636253b635fdcf97', 'name' => 'Steps', 'pos' => 1, 'checkItems' => [
					['name' => 'Step two', 'state' => 'incomplete', 'pos' => 2],
					['name' => 'Step one', 'state' => 'complete', 'pos' => 1],
				]],
			],
			'actions' => [
				['type' => 'commentCard', 'idMemberCreator' => 'm2', 'date' => '2025-04-17T09:00:00.000Z', 'data' => ['text' => 'Agreed', 'card' => ['id' => '67ff872d636253b635fdcf97']]],
				['type' => 'commentCard', 'idMemberCreator' => 'm1', 'date' => '2025-04-16T12:00:00.000Z', 'data' => ['text' => 'Looks good', 'card' => ['id' => '67ff872d636253b635fdcf97']]],
				['type' => 'createCard', 'idMemberCreator' => 'm1', 'date' => '2025-04-16T10:29:55.000Z', 'data' => ['card' => ['id' => '67ff872d636253b635fdcf97']]],
			],
		];
	}
}
