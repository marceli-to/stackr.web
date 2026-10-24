<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * A card can go into any list on a board the user is a member of.
 */
class CardMoveRequest extends MoveRequest
{
	/**
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			...parent::rules(),
			'stack_id' => ['sometimes', 'integer', $this->boardStack()],
		];
	}

	/**
	 * An active list on one of the user's boards.
	 */
	protected function boardStack(): Exists
	{
		/** @var User $user */
		$user = $this->user();

		return Rule::exists('stacks', 'id')
			->whereIn('project_id', $user->memberships()->pluck('project_id'))
			->whereNull('archived_at');
	}

	/**
	 * @return array<string, string>
	 */
	public function messages(): array
	{
		return [
			'stack_id.exists' => __('The list is not on one of your boards.'),
		];
	}
}
