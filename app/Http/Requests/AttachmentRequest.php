<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class AttachmentRequest extends FormRequest
{
	/**
	 * Up to 20 files of up to 20 MB each.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'files' => ['required', 'array', 'max:20'],
			'files.*' => ['file', 'max:20480'],
		];
	}

	/**
	 * @return list<UploadedFile>
	 */
	public function uploads(): array
	{
		/** @var list<UploadedFile> */
		return $this->file('files');
	}
}
