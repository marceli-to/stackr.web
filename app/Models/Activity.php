<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something that happened to a card, for its activity log. `data` keeps the
 * names as they were (lists, files, people), so the log reads right after
 * they change.
 *
 * @property int $id
 * @property int $card_id
 * @property int|null $user_id
 * @property string $type
 * @property array<string, mixed>|null $data
 * @property bool $via_agent
 * @property CarbonImmutable|null $created_at
 */
#[Fillable(['type', 'data', 'via_agent'])]
class Activity extends Model
{
	public const UPDATED_AT = null;

	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'data' => 'array',
			'via_agent' => 'boolean',
		];
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
