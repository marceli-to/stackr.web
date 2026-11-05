<?php

namespace App\Support;

use Illuminate\Support\Str;

class Markdown
{
	/**
	 * Markdown to HTML, with raw HTML stripped and unsafe links dropped. With
	 * a repository URL, commit hashes link to their commits.
	 */
	public static function toHtml(?string $text, ?string $repository = null): ?string
	{
		if (blank($text)) {
			return null;
		}

		return Str::markdown($text, [
			'html_input' => 'strip',
			'allow_unsafe_links' => false,
			'max_nesting_level' => 20,
		], blank($repository) ? [] : [new CommitLinks($repository)]);
	}
}
