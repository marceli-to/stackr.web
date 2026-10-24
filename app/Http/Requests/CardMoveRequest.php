<?php

namespace App\Http\Requests;

use App\Models\Card;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * A card can be dropped into any list on its own project's board.
 */
class CardMoveRequest extends MoveRequest
{
	/**
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		/** @var Card $card */
		$card = $this->route('card');

		return [
			...parent::rules(),
			'stack_id' => ['sometimes', 'integer', Rule::exists('stacks', 'id')->where('project_id', $card->stack->project_id)->whereNull('archived_at')],
		];
	}

	/**
	 * @return array<string, string>
	 */
	public function messages(): array
	{
		return [
			'stack_id.exists' => __('The list does not belong to this project.'),
		];
	}
}
