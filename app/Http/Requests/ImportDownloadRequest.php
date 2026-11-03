<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ImportDownloadRequest extends FormRequest
{
	/**
	 * The Trello key and token, sent with every file and never stored.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'key' => ['required', 'string', 'max:100'],
			'token' => ['required', 'string', 'max:200'],
		];
	}
}
