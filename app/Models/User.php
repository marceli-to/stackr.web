<?php

namespace App\Models;

use App\Enums\ProjectRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
	/** @use HasFactory<UserFactory> */
	use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

	/**
	 * Get the attributes that should be cast.
	 *
	 * @return array<string, string>
	 */
	protected function casts(): array
	{
		return [
			'email_verified_at' => 'datetime',
			'password' => 'hashed',
			'two_factor_confirmed_at' => 'datetime',
		];
	}

	/**
	 * Delete the projects a user owns along with the user.
	 */
	protected static function booted(): void
	{
		static::deleting(function (User $user): void {
			$user->projects()
				->wherePivot('role', ProjectRole::Owner->value)
				->get()
				->each->delete();
		});
	}

	/**
	 * @return BelongsToMany<Project, $this, ProjectMembership>
	 */
	public function projects(): BelongsToMany
	{
		return $this->belongsToMany(Project::class)
			->using(ProjectMembership::class)
			->withPivot('id', 'role')
			->withTimestamps();
	}

	/**
	 * @return HasMany<ProjectMembership, $this>
	 */
	public function memberships(): HasMany
	{
		return $this->hasMany(ProjectMembership::class);
	}
}
