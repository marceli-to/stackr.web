<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ThumbnailTest extends TestCase
{
	use RefreshDatabase;

	private User $user;

	private Card $card;

	protected function setUp(): void
	{
		parent::setUp();

		Storage::fake(Attachment::DISK);

		$this->user = User::factory()->create();
		$board = Board::factory()->ownedBy($this->user)->create();
		$this->card = Card::factory()->for(Stack::factory()->for($board))->create();
	}

	private function upload(UploadedFile $file): Attachment
	{
		$this->actingAs($this->user)
			->post(route('attachments.store', $this->card), ['files' => [$file]])
			->assertSessionHasNoErrors();

		return $this->card->attachments()->firstOrFail();
	}

	public function test_large_images_get_a_webp_thumbnail()
	{
		$attachment = $this->upload(UploadedFile::fake()->image('photo.jpg', 1600, 1200));

		$this->assertNotNull($attachment->thumb_path);
		$size = getimagesize(Storage::disk(Attachment::DISK)->path($attachment->thumb_path));
		$this->assertSame([640, 480], [$size[0] ?? null, $size[1] ?? null]);
		$this->assertSame('image/webp', $size['mime'] ?? null);

		$this->actingAs($this->user)
			->get(route('attachments.thumbnail', $attachment))
			->assertOk()
			->assertHeader('Content-Type', 'image/webp');
	}

	public function test_small_images_and_other_files_get_none()
	{
		$small = $this->upload(UploadedFile::fake()->image('icon.png', 300, 200));
		$this->assertNull($small->thumb_path);

		// The thumbnail route falls back to the image itself.
		$this->actingAs($this->user)
			->get(route('attachments.thumbnail', $small))
			->assertOk()
			->assertHeader('Content-Type', 'image/png');
	}

	public function test_deleting_an_image_deletes_its_thumbnail()
	{
		$attachment = $this->upload(UploadedFile::fake()->image('photo.jpg', 1600, 1200));
		$thumb = (string) $attachment->thumb_path;

		$this->actingAs($this->user)->delete(route('attachments.destroy', $attachment));

		Storage::disk(Attachment::DISK)->assertMissing($thumb);
	}

	public function test_the_command_backfills_missing_thumbnails()
	{
		$attachment = $this->upload(UploadedFile::fake()->image('photo.jpg', 1600, 1200));
		Storage::disk(Attachment::DISK)->delete((string) $attachment->thumb_path);
		$attachment->update(['thumb_path' => null]);

		$this->artisan('app:make-thumbnails')->expectsOutput('Made 1 thumbnails.')->assertSuccessful();

		$this->assertNotNull($attachment->fresh()->thumb_path);
	}

	public function test_outsiders_cant_see_thumbnails()
	{
		$attachment = $this->upload(UploadedFile::fake()->image('photo.jpg', 1600, 1200));

		$this->actingAs(User::factory()->create())
			->get(route('attachments.thumbnail', $attachment))
			->assertForbidden();
	}
}
