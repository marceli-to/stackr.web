<?php

namespace App\Http\Requests;

use App\Models\Board;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class InviteMemberRequest extends FormRequest
{
	protected function prepareForValidation(): void
	{
		if (is_string($this->input('email'))) {
			$this->merge(['email' => Str::lower(trim($this->input('email')))]);
		}
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		/** @var Board $board */
		$board = $this->route('board');

		return [
			'email' => [
				'required', 'string', 'email', 'max:255',
				function (string $attribute, mixed $value, Closure $fail) use ($board): void {
					$user = User::where('email', $value)->first();

					if ($user !== null && $board->roleOf($user) !== null) {
						$fail(__(':name is already on this board.', ['name' => $user->name]));
					}
				},
			],
		];
	}

	public function email(): string
	{
		return $this->string('email')->toString();
	}
}
