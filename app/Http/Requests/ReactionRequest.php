<?php

namespace App\Http\Requests;

use App\Models\Reaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReactionRequest extends FormRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'emoji' => ['required', 'string', Rule::in(Reaction::EMOJIS)],
		];
	}
}
