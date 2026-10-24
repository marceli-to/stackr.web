<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * A user's membership in a project.
 *
 * @property int $id
 * @property int $project_id
 * @property int $user_id
 * @property ProjectRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
class ProjectMembership extends Pivot
{
	protected $table = 'project_user';

	public $incrementing = true;

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'role' => ProjectRole::class,
		];
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
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
