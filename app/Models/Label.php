<?php

namespace App\Models;

use App\Enums\LabelColor;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * A coloured label, defined per board and put on its cards.
 *
 * @property int $id
 * @property int $board_id
 * @property string|null $name
 * @property LabelColor $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'color'])]
class Label extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'color' => LabelColor::class,
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
	 * @return BelongsToMany<Card, $this>
	 */
	public function cards(): BelongsToMany
	{
		return $this->belongsToMany(Card::class);
	}
}
