<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Where a copy goes, what it's called and what it keeps.
 */
class CardCopyRequest extends CardMoveRequest
{
	/**
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			...parent::rules(),
			'stack_id' => ['required', 'integer', $this->boardStack()],
			'title' => ['required', 'string', 'max:500'],
			'keep' => ['array'],
			'keep.*' => ['string', 'in:checklists,labels,members,attachments'],
		];
	}
}
