<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BackgroundRequest extends FormRequest
{
	/**
	 * A photo of up to 20 MB, like attachments.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
		];
	}
}
