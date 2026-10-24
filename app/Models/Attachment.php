<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A file on a card. Files live on the private "local" disk under
 * attachments/{board}/{card}/ and are served by AttachmentController to
 * board members only.
 *
 * @property int $id
 * @property int $card_id
 * @property int|null $user_id
 * @property string $path
 * @property string|null $thumb_path
 * @property string $name
 * @property string $mime_type
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['path', 'thumb_path', 'name', 'mime_type', 'size', 'width', 'height'])]
class Attachment extends Model
{
	public const DISK = 'local';

	protected static function booted(): void
	{
		static::deleted(function (Attachment $attachment): void {
			Storage::disk(self::DISK)->delete(array_filter([$attachment->path, $attachment->thumb_path]));
		});
	}

	/**
	 * The folder that holds a card's files.
	 */
	public static function directoryFor(int $boardId, int $cardId): string
	{
		return "attachments/{$boardId}/{$cardId}";
	}

	public function isImage(): bool
	{
		return in_array($this->mime_type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'], true);
	}

	/**
	 * @return BelongsTo<Card, $this>
	 */
	public function card(): BelongsTo
	{
		return $this->belongsTo(Card::class);
	}

	/**
	 * @return BelongsTo<User, $this>
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
