<?php

namespace App\Actions\Imports;

use App\Models\User;
use Illuminate\Support\Str;

class CreateImportedUser
{
	/**
	 * An account for someone whose work comes in with an import: verified
	 * straight away, with a random password, and no email sent. They sign
	 * in with "Forgot password" or the password the importer passes on.
	 *
	 * @return array{user: User, password: string}
	 */
	public function handle(string $name, string $email): array
	{
		$password = Str::password(16, symbols: false);

		$user = new User(['name' => $name, 'email' => strtolower($email), 'password' => $password]);
		$user->forceFill(['email_verified_at' => now()])->save();

		return ['user' => $user, 'password' => $password];
	}
}
