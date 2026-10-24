<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BackgroundTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Board $board;

	protected function setUp(): void
	{
		parent::setUp();

		Storage::fake(Attachment::DISK);

		$this->user = User::factory()->create();
		$this->board = Board::factory()->ownedBy($this->user)->create();
	}

	public function test_a_large_photo_is_shrunk_and_shown_behind_the_board()
	{
		$this->actingAs($this->user)
			->post(route('boards.background.store', $this->board), ['image' => UploadedFile::fake()->image('wall.jpg', 3000, 2000)])
			->assertSessionHasNoErrors();

		$path = (string) $this->board->fresh()->background_path;
		$this->assertStringEndsWith('.webp', $path);
		$this->assertSame(2400, getimagesize(Storage::disk(Attachment::DISK)->path($path))[0] ?? null);

		$this->actingAs($this->user)
			->get(route('boards.show', $this->board))
			->assertInertia(fn (Assert $page) => $page->where('board.background_url', fn (string $url) => str_contains($url, '/background?v=')));

		$this->actingAs($this->user)->get(route('boards.background', $this->board))->assertOk();
		$this->actingAs(User::factory()->create())->get(route('boards.background', $this->board))->assertForbidden();
	}

	public function test_replacing_and_removing_delete_the_old_file()
	{
		$this->actingAs($this->user)
			->post(route('boards.background.store', $this->board), ['image' => UploadedFile::fake()->image('a.png', 800, 600)]);
		$first = (string) $this->board->fresh()->background_path;
		$this->assertStringEndsWith('.png', $first);

		$this->actingAs($this->user)
			->post(route('boards.background.store', $this->board), ['image' => UploadedFile::fake()->image('b.png', 800, 600)]);
		Storage::disk(Attachment::DISK)->assertMissing($first);
		$second = (string) $this->board->fresh()->background_path;

		$this->actingAs($this->user)->delete(route('boards.background.destroy', $this->board));
		Storage::disk(Attachment::DISK)->assertMissing($second);
		$this->assertNull($this->board->fresh()->background_path);
	}

	public function test_only_images_are_accepted()
	{
		$this->actingAs($this->user)
			->post(route('boards.background.store', $this->board), ['image' => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])
			->assertSessionHasErrors('image');
	}
}
