<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<User, $this, ProjectMembership>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ProjectMembership::class)
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    /**
     * @return HasMany<Stack, $this>
     */
    public function stacks(): HasMany
    {
        return $this->hasMany(Stack::class)->orderBy('position');
    }

    /**
     * @return HasManyThrough<Card, Stack, $this>
     */
    public function cards(): HasManyThrough
    {
        return $this->hasManyThrough(Card::class, Stack::class);
    }

    public function roleOf(User $user): ?ProjectRole
    {
        return $this->members()->whereKey($user->id)->first()?->pivot->role;
    }
}
