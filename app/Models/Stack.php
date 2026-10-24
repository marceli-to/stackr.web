<?php

namespace App\Models;

use Database\Factories\StackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A list of cards on a project's board.
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $position
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'position'])]
class Stack extends Model
{
    /** @use HasFactory<StackFactory> */
    use HasFactory;

    /**
     * The database removes the list's cards; their files go here.
     */
    protected static function booted(): void
    {
        static::deleting(function (Stack $stack): void {
            foreach ($stack->cards()->pluck('id') as $cardId) {
                Storage::disk(Attachment::DISK)->deleteDirectory(
                    Attachment::directoryFor($stack->project_id, $cardId),
                );
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class)->orderBy('position');
    }
}
