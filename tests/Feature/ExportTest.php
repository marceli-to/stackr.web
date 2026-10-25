<?php

namespace Tests\Feature;

use App\Enums\LabelColor;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ExportTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Board $board;

	protected function setUp(): void
	{
		parent::setUp();

		Storage::fake(Attachment::DISK);

		$this->user = User::factory()->create(['name' => 'Ada']);
		$this->board = Board::factory()->ownedBy($this->user)->create(['name' => 'Launch Plan']);
	}

	public function test_a_member_downloads_the_board_with_its_active_cards_and_files()
	{
		$stack = Stack::factory()->for($this->board)->create(['name' => 'To do']);
		$card = Card::factory()->for($stack)->create(['title' => 'Write copy']);
		Card::factory()->for($stack)->create(['title' => 'Old idea', 'archived_at' => now()]);
		$gone = Stack::factory()->for($this->board)->create(['name' => 'Gone', 'archived_at' => now()]);
		Card::factory()->for($gone)->create(['title' => 'In an archived list']);

		$label = $this->board->labels()->create(['name' => 'Urgent', 'color' => LabelColor::cases()[0]]);
		$card->labels()->attach($label);
		$card->checklists()->create(['title' => 'Steps'])->items()->create(['title' => 'Draft']);
		$card->comments()->forceCreate(['user_id' => $this->user->id, 'body' => 'First pass done']);

		$path = Attachment::directoryFor($this->board->id, $card->id).'/a.txt';
		Storage::disk(Attachment::DISK)->put($path, 'hello');
		$card->attachments()->forceCreate(['user_id' => $this->user->id, 'path' => $path, 'name' => '../brief.txt', 'mime_type' => 'text/plain', 'size' => 5]);

		$response = $this->actingAs($this->user)->get(route('boards.export', $this->board));

		$response->assertOk()->assertDownload('stackr-launch-plan-'.now()->format('Y-m-d').'.zip');

		$zip = new ZipArchive;
		$this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));
		$data = json_decode((string) $zip->getFromName('board.json'), true);

		$this->assertSame('Launch Plan', $data['board']['name']);
		$this->assertSame(['To do'], array_column($data['lists'], 'name'));
		$this->assertSame(['Write copy'], array_column($data['lists'][0]['cards'], 'title'));

		$exported = $data['lists'][0]['cards'][0];
		$this->assertSame('Urgent', $exported['labels'][0]['name']);
		$this->assertSame('Draft', $exported['checklists'][0]['items'][0]['title']);
		$this->assertSame('Ada', $exported['comments'][0]['author']);

		$file = $exported['attachments'][0]['file'];
		$this->assertStringStartsWith("attachments/{$card->id}/", $file);
		$this->assertStringNotContainsString('..', $file);
		$this->assertSame('hello', $zip->getFromName($file));

		$zip->close();
	}

	public function test_strangers_cannot_export()
	{
		$this->actingAs(User::factory()->create())
			->get(route('boards.export', $this->board))
			->assertForbidden();
	}
}
