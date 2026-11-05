<?php

namespace App\Http\Requests;

use App\Models\Stack;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Moving a list to another board: one the user is a member of.
 */
class StackBoardMoveRequest extends FormRequest
{
	/**
	 * Only people on the list's board, before anything is validated.
	 */
	public function authorize(): bool
	{
		/** @var Stack $stack */
		$stack = $this->route('stack');

		return Gate::allows('update', $stack->board);
	}

	/**
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		/** @var Stack $stack */
		$stack = $this->route('stack');
		/** @var User $user */
		$user = $this->user();

		return [
			'board_id' => [
				'required',
				'integer',
				Rule::notIn([$stack->board_id]),
				Rule::exists('board_user', 'board_id')->where('user_id', $user->id),
			],
		];
	}

	/**
	 * @return array<string, string>
	 */
	public function messages(): array
	{
		return [
			'board_id.*' => __('Pick another of your boards.'),
		];
	}
}
