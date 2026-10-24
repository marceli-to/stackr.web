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
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property string|null $url
 * @property ProjectColor $color
 * @property string|null $background_path
 * @property string|null $invite_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'url', 'color', 'background_path'])]
class Project extends Model
{
	/** @use HasFactory<ProjectFactory> */
	use HasFactory;

	protected $hidden = ['invite_token'];

	/**
	 * URLs show the random key, not the id.
	 */
	public function getRouteKeyName(): string
	{
		return 'key';
	}

	public static function newKey(): string
	{
		do {
			$key = Str::lower(Str::random(10));
		} while (static::where('key', $key)->exists());

		return $key;
	}

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
		static::creating(function (Project $project): void {
			$project->key ??= static::newKey();
		});

		static::deleting(function (Project $project): void {
			Storage::disk(Attachment::DISK)->deleteDirectory("attachments/{$project->id}");
			Storage::disk(Attachment::DISK)->deleteDirectory("backgrounds/{$project->id}");
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
	 * People who asked to hear about changes anywhere on this board.
	 *
	 * @return BelongsToMany<User, $this>
	 */
	public function watchers(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'project_watchers');
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
