<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A board imported from elsewhere whose files are still to be fetched.
 * Each pending file is downloaded in its own request (or loop step, from
 * the command), so a big board never runs into a time limit. The row goes
 * once every file is in or the importer gives up on the rest.
 *
 * @property int $id
 * @property int $board_id
 * @property int $user_id
 * @property string $source
 * @property list<array{card_id: int, url: string, name: string, file_name: string, mime_type: string|null, user_id: int, date: string, cover: bool}> $pending
 * @property list<array{name: string, card: string, reason: string}> $failed
 * @property int $total
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['source', 'pending', 'failed', 'total'])]
class BoardImport extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'pending' => 'array',
			'failed' => 'array',
		];
	}

	/**
	 * @return BelongsTo<Board, $this>
	 */
	public function board(): BelongsTo
	{
		return $this->belongsTo(Board::class);
	}

	/**
	 * @return BelongsTo<User, $this>
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
