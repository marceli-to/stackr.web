<?php

namespace App\Models;

use App\Enums\ProjectColor;
use App\Enums\ProjectRole;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $url
 * @property ProjectColor $color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'url', 'color'])]
class Project extends Model
{
	/** @use HasFactory<ProjectFactory> */
	use HasFactory;

	/**
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'color' => ProjectColor::class,
		];
	}

	/**
	 * The database removes the project's rows; its files go here.
	 */
	protected static function booted(): void
	{
		static::deleting(function (Project $project): void {
			Storage::disk(Attachment::DISK)->deleteDirectory("attachments/{$project->id}");
		});
	}

	/**
	 * @return HasMany<Label, $this>
	 */
	public function labels(): HasMany
	{
		return $this->hasMany(Label::class)->orderBy('id');
	}

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
	 * @return HasMany<ProjectInvitation, $this>
	 */
	public function invitations(): HasMany
	{
		return $this->hasMany(ProjectInvitation::class)->orderBy('email');
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

	/**
	 * The project's members with their users, the owner first and the rest by name.
	 *
	 * @return Collection<int, ProjectMembership>
	 */
	public function membershipsInOrder(): Collection
	{
		return ProjectMembership::with('user')->where('project_id', $this->id)->get()
			->sortBy(fn (ProjectMembership $membership) => [
				$membership->role !== ProjectRole::Owner,
				Str::lower($membership->user->name),
			])
			->values()
			->toBase();
	}

	public function roleOf(User $user): ?ProjectRole
	{
		return $this->members()->whereKey($user->id)->first()?->pivot->role;
	}
}
