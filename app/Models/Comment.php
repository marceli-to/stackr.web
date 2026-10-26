<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $card_id
 * @property int|null $user_id
 * @property string $body
 * @property bool $via_agent
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['body', 'via_agent'])]
class Comment extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
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

	/**
	 * @return HasMany<Reaction, $this>
	 */
	public function reactions(): HasMany
	{
		return $this->hasMany(Reaction::class)->oldest('id');
	}
}
