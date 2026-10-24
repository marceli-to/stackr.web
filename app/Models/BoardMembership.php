<?php

namespace App\Models;

use App\Enums\BoardRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A user's membership in a board.
 *
 * @property int $id
 * @property int $board_id
 * @property int $user_id
 * @property BoardRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class BoardMembership extends Pivot
{
	protected $table = 'board_user';

	public $incrementing = true;

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'role' => BoardRole::class,
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
