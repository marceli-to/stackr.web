<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $checklist_id
 * @property string $title
 * @property CarbonImmutable|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'completed_at'])]
class ChecklistItem extends Model
{
	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'completed_at' => 'datetime',
		];
	}

	/**
	 * @return BelongsTo<Checklist, $this>
	 */
	public function checklist(): BelongsTo
	{
		return $this->belongsTo(Checklist::class);
	}
}
