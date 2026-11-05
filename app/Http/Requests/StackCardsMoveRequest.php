<?php

namespace App\Http\Requests;

use App\Models\Stack;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Moving every card of a list: the target is another active list on the
 * same board.
 */
class StackCardsMoveRequest extends FormRequest
{
	/**
	 * Only people on the board, before anything is validated.
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

		return [
			'stack_id' => [
				'required',
				'integer',
				Rule::notIn([$stack->id]),
				Rule::exists('stacks', 'id')
					->where('board_id', $stack->board_id)
					->whereNull('archived_at'),
			],
		];
	}

	/**
	 * @return array<string, string>
	 */
	public function messages(): array
	{
		return [
			'stack_id.*' => __('Pick another list on this board.'),
		];
	}
}
