<?php

namespace App\Http\Requests;

use App\Enums\LabelColor;
use App\Models\Card;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardUpdateRequest extends FormRequest
{
	/**
	 * Every field is optional, so one control can save just its own value.
	 *
	 * @return array<string, ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		/** @var Card $card */
		$card = $this->route('card');

		return [
			'title' => ['sometimes', 'required', 'string', 'max:500'],
			'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
			'start_at' => ['sometimes', 'nullable', 'date'],
			'due_at' => ['sometimes', 'nullable', 'date', Rule::when($this->filled('start_at'), 'after_or_equal:start_at')],
			'completed' => ['sometimes', 'boolean'],
			'cover_color' => ['sometimes', 'nullable', Rule::enum(LabelColor::class)],
			'cover_attachment_id' => [
				'sometimes', 'nullable', 'integer',
				Rule::exists('attachments', 'id')->where('card_id', $card->id)->where(fn ($query) => $query->where('mime_type', 'like', 'image/%')),
			],
		];
	}
}
