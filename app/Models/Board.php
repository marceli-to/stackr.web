<?php

namespace App\Models;

use App\Enums\BoardColor;
use App\Enums\BoardRole;
use Database\Factories\BoardFactory;
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
 * @property BoardColor $color
 * @property string|null $background_path
 * @property string|null $invite_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'url', 'color', 'background_path'])]
class Board extends Model
{
	/** @use HasFactory<BoardFactory> */
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
			'color' => BoardColor::class,
		];
	}

	/**
	 * The database removes the board's rows; its files go here.
	 */
	protected static function booted(): void
	{
		static::creating(function (Board $board): void {
			$board->key ??= static::newKey();
		});

		static::deleting(function (Board $board): void {
			Storage::disk(Attachment::DISK)->deleteDirectory("attachments/{$board->id}");
			Storage::disk(Attachment::DISK)->deleteDirectory("backgrounds/{$board->id}");
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
	 * @return BelongsToMany<User, $this, BoardMembership>
	 */
	public function members(): BelongsToMany
	{
		return $this->belongsToMany(User::class)
			->using(BoardMembership::class)
			->withPivot('id', 'role')
			->withTimestamps();
	}

	/**
	 * @return HasMany<BoardInvitation, $this>
	 */
	public function invitations(): HasMany
	{
		return $this->hasMany(BoardInvitation::class)->orderBy('email');
	}

	/**
	 * People who asked to hear about changes anywhere on this board.
	 *
	 * @return BelongsToMany<User, $this>
	 */
	public function watchers(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'board_watchers');
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
	 * The board's members with their users, the owner first and the rest by name.
	 *
	 * @return Collection<int, BoardMembership>
	 */
	public function membershipsInOrder(): Collection
	{
		return BoardMembership::with('user')->where('board_id', $this->id)->get()
			->sortBy(fn (BoardMembership $membership) => [
				$membership->role !== BoardRole::Owner,
				Str::lower($membership->user->name),
			])
			->values()
			->toBase();
	}

	public function roleOf(User $user): ?BoardRole
	{
		return $this->members()->whereKey($user->id)->first()?->pivot->role;
	}
}
