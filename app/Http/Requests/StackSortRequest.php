<?php

namespace App\Http\Requests;

use App\Enums\CardSort;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StackSortRequest extends FormRequest
{
	/**
	 * One of the orders in `CardSort`.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'by' => ['required', Rule::enum(CardSort::class)],
		];
	}
}
