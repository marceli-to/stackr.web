<?php

namespace App\Http\Requests;

use App\Models\Project;
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
		/** @var Project $project */
		$project = $this->route('project');

		return [
			'email' => [
				'required', 'string', 'email', 'max:255',
				function (string $attribute, mixed $value, Closure $fail) use ($project): void {
					$user = User::where('email', $value)->first();

					if ($user !== null && $project->roleOf($user) !== null) {
						$fail(__(':name is already on this project.', ['name' => $user->name]));
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
