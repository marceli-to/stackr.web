<?php

namespace App\Http\Requests;

use App\Enums\BoardColor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardRequest extends FormRequest
{
	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'name' => ['required', 'string', 'max:255'],
			'description' => ['nullable', 'string', 'max:2000'],
			'url' => ['nullable', 'url:http,https', 'max:2048'],
			'color' => ['sometimes', Rule::enum(BoardColor::class)],
		];
	}
}
