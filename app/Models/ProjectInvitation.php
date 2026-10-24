<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * An emailed invitation to join a project, waiting to be accepted.
 *
 * @property int $id
 * @property int $project_id
 * @property string $email
 * @property string $token
 * @property int|null $invited_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Project $project
 * @property-read User|null $inviter
 */
#[Fillable(['email', 'token', 'invited_by'])]
class ProjectInvitation extends Model
{
	/**
	 * Invitations older than this can no longer be accepted.
	 */
	public const VALID_DAYS = 14;

	protected $hidden = ['token'];

	public static function newToken(): string
	{
		return Str::random(64);
	}

	public function isExpired(): bool
	{
		return $this->updated_at === null || $this->updated_at->lt(now()->subDays(self::VALID_DAYS));
	}

	/**
	 * @return BelongsTo<Project, $this>
	 */
	public function project(): BelongsTo
	{
		return $this->belongsTo(Project::class);
	}

	/**
	 * @return BelongsTo<User, $this>
	 */
	public function inviter(): BelongsTo
	{
		return $this->belongsTo(User::class, 'invited_by');
	}
}
