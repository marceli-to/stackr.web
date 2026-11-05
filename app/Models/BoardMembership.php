<?php

namespace App\Models;

use App\Enums\BoardRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A user's membership in a board.
 *
 * @property int $id
 * @property int $board_id
 * @property int $user_id
 * @property BoardRole $role
 * @property string|null $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class BoardMembership extends Pivot
{
	protected $table = 'board_user';

	public $incrementing = true;

	/**
	 * A user's boards as the overview shows them: the ones they placed, by
	 * position, then the rest by name. Expects the boards loaded.
	 *
	 * @param  Collection<int, BoardMembership>|\Illuminate\Database\Eloquent\Collection<int, BoardMembership>  $memberships
	 * @return Collection<int, BoardMembership>
	 */
	public static function inOverviewOrder(Collection $memberships): Collection
	{
		return collect($memberships->all())
			->sort(function (BoardMembership $a, BoardMembership $b): int {
				if ($a->position !== null && $b->position !== null) {
					return strcmp($a->position, $b->position) ?: $a->id <=> $b->id;
				}

				if ($a->position !== null || $b->position !== null) {
					return $a->position !== null ? -1 : 1;
				}

				return strnatcasecmp($a->board->name, $b->board->name) ?: $a->id <=> $b->id;
			})
			->values();
	}

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
