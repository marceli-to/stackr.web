<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A drag & drop move: the item's new index among its siblings, and for
 * cards optionally the list it was dropped into.
 */
class MoveRequest extends FormRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'index' => ['required', 'integer', 'min:0'],
			'stack_id' => ['sometimes', 'integer'],
		];
	}
}
