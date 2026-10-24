<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StackLimitRequest extends FormRequest
{
	/**
	 * No limit, or 1 to 999 cards.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'wip_limit' => ['present', 'nullable', 'integer', 'min:1', 'max:999'],
		];
	}
}
