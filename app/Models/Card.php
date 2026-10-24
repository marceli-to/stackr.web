<?php

namespace App\Models;

use App\Enums\LabelColor;
use Carbon\CarbonImmutable;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $stack_id
 * @property string $title
 * @property string|null $description
 * @property string $position
 * @property CarbonImmutable|null $start_at
 * @property CarbonImmutable|null $due_at
 * @property CarbonImmutable|null $due_reminded_at
 * @property CarbonImmutable|null $completed_at
 * @property CarbonImmutable|null $archived_at
 * @property int|null $cover_attachment_id
 * @property LabelColor|null $cover_color
 * @property bool $is_template
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['title', 'description', 'position', 'start_at', 'due_at', 'due_reminded_at', 'completed_at', 'archived_at', 'cover_attachment_id', 'cover_color', 'is_template'])]
class Card extends Model
{
	/** @use HasFactory<CardFactory> */
	use HasFactory;

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'start_at' => 'datetime',
			'due_at' => 'datetime',
			'due_reminded_at' => 'datetime',
			'completed_at' => 'datetime',
			'archived_at' => 'datetime',
			'cover_color' => LabelColor::class,
			'is_template' => 'boolean',
		];
	}

	/**
	 * Only the cards that aren't archived.
	 *
	 * @param  Builder<Card>  $query
	 */
	#[Scope]
	protected function active(Builder $query): void
	{
		$query->whereNull($query->qualifyColumn('archived_at'));
	}

	/**
	 * The database removes a card's rows; its files go here.
	 */
	protected static function booted(): void
	{
		static::deleting(function (Card $card): void {
			Storage::disk(Attachment::DISK)->deleteDirectory(
				Attachment::directoryFor($card->stack->board_id, $card->id),
			);
		});
	}

	/**
	 * @return BelongsTo<Stack, $this>
	 */
	public function stack(): BelongsTo
	{
		return $this->belongsTo(Stack::class);
	}

	/**
	 * @return BelongsToMany<Label, $this>
	 */
	public function labels(): BelongsToMany
	{
		return $this->belongsToMany(Label::class)->orderBy('labels.id');
	}

	/**
	 * The board members assigned to the card.
	 *
	 * @return BelongsToMany<User, $this>
	 */
	public function members(): BelongsToMany
	{
		return $this->belongsToMany(User::class)->orderBy('users.name');
	}

	/**
	 * People who asked to hear about changes to this card.
	 *
	 * @return BelongsToMany<User, $this>
	 */
	public function watchers(): BelongsToMany
	{
		return $this->belongsToMany(User::class, 'card_watchers');
	}

	/**
	 * @return HasMany<Checklist, $this>
	 */
	public function checklists(): HasMany
	{
		return $this->hasMany(Checklist::class)->orderBy('id');
	}

	/**
	 * Newest first, like the comments.
	 *
	 * @return HasMany<Attachment, $this>
	 */
	public function attachments(): HasMany
	{
		return $this->hasMany(Attachment::class)->latest('id');
	}

	/**
	 * @return BelongsTo<Attachment, $this>
	 */
	public function cover(): BelongsTo
	{
		return $this->belongsTo(Attachment::class, 'cover_attachment_id');
	}

	/**
	 * Newest first.
	 *
	 * @return HasMany<Activity, $this>
	 */
	public function activities(): HasMany
	{
		return $this->hasMany(Activity::class)->latest('id');
	}

	/**
	 * Newest first.
	 *
	 * @return HasMany<Comment, $this>
	 */
	public function comments(): HasMany
	{
		return $this->hasMany(Comment::class)->latest('id');
	}
}
