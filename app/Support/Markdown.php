<?php

namespace App\Support;

use Illuminate\Support\Str;

class Markdown
{
	/**
	 * Markdown to HTML, with raw HTML stripped and unsafe links dropped.
	 */
	public static function toHtml(?string $text): ?string
	{
		if (blank($text)) {
			return null;
		}

		return Str::markdown($text, [
			'html_input' => 'strip',
			'allow_unsafe_links' => false,
			'max_nesting_level' => 20,
		]);
	}
}
