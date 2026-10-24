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
 * @property string $title
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title'])]
class Checklist extends Model
{
	/**
	 * @return BelongsTo<Card, $this>
	 */
	public function card(): BelongsTo
	{
		return $this->belongsTo(Card::class);
	}

	/**
	 * Items in the order they were added.
	 *
	 * @return HasMany<ChecklistItem, $this>
	 */
	public function items(): HasMany
	{
		return $this->hasMany(ChecklistItem::class)->orderBy('id');
	}
}
