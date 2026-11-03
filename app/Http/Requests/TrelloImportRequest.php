<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TrelloImportRequest extends FormRequest
{
	/** @var array<string, mixed>|null */
	private ?array $export = null;

	/**
	 * The export (up to 20 MB of JSON, the server's upload limit), the board's name, and for each
	 * Trello member (by id): an existing user, a new account (admins only)
	 * or nobody.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		$create = $this->user()?->isAdmin() ? 'create' : null;

		return [
			'file' => ['required', 'file', 'max:20480'],
			'name' => ['required', 'string', 'max:250'],
			'people' => ['array'],
			'people.*.choice' => ['required', Rule::in(array_filter(['user', 'nobody', $create]))],
			'people.*.user_id' => ['required_if:people.*.choice,user', 'nullable', 'integer', Rule::in($this->user()?->knownUsers()->pluck('users.id')->all() ?? [])],
			'people.*.name' => ['required_if:people.*.choice,create', 'nullable', 'string', 'max:255'],
			'people.*.email' => ['required_if:people.*.choice,create', 'nullable', 'email', 'max:255', 'distinct:ignore_case', Rule::unique('users', 'email')],
		];
	}

	/**
	 * @return array<string, string>
	 */
	public function messages(): array
	{
		return [
			'people.*.choice.in' => 'Only admins can create accounts.',
			'people.*.email.unique' => 'Someone has this email already; pick them instead.',
			'people.*.email.distinct' => 'Two new accounts can\'t share an email.',
		];
	}

	/**
	 * @return array<int, callable>
	 */
	public function after(): array
	{
		return [function (Validator $validator): void {
			if ($validator->errors()->has('file')) {
				return;
			}

			$file = $this->file('file');
			$data = $file !== null && ! is_array($file) ? json_decode((string) $file->get(), true) : null;

			if (! is_array($data) || ! isset($data['lists'], $data['cards'], $data['members'])) {
				$validator->errors()->add('file', 'This is not a Trello board export.');

				return;
			}

			$this->export = $data;
		}];
	}

	/**
	 * The decoded export, once validated.
	 *
	 * @return array<string, mixed>
	 */
	public function export(): array
	{
		return $this->export ?? [];
	}
}
