<?php

namespace App\Http\Requests;

use App\Models\Card;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * A new checklist, optionally with the items of another one on the same board.
 */
class ChecklistStoreRequest extends ChecklistRequest
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
			'copy_from' => [
				'nullable', 'integer',
				Rule::exists('checklists', 'id')->whereIn('card_id', $card->stack->board->cards()->pluck('cards.id')),
			],
		];
	}
}
