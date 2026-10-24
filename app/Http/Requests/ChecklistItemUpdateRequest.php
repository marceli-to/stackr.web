<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChecklistItemUpdateRequest extends FormRequest
{
	/**
	 * Rename an item, or tick it off and back on.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'title' => ['sometimes', 'required', 'string', 'max:500'],
			'done' => ['sometimes', 'boolean'],
		];
	}
}
