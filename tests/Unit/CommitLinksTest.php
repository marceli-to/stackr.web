<?php

namespace Tests\Unit;

use App\Support\Markdown;
use Tests\TestCase;

class CommitLinksTest extends TestCase
{
	private const REPO = 'https://github.com/marceli-to/stackr.web/';

	private function link(string $hash, bool $code = false): string
	{
		$label = $code ? "<code>{$hash}</code>" : $hash;

		return '<a href="https://github.com/marceli-to/stackr.web/commit/'.$hash.'">'.$label.'</a>';
	}

	public function test_hashes_link_to_their_commits()
	{
		$this->assertSame(
			'<p>Fixed in '.$this->link('93a2f49').', see '.$this->link('b38f452', true).".</p>\n",
			Markdown::toHtml('Fixed in 93a2f49, see `b38f452`.', self::REPO),
		);

		$full = '0123456789abcdef0123456789abcdef01234567';
		$this->assertStringContainsString($this->link($full), (string) Markdown::toHtml($full, self::REPO));
	}

	public function test_other_text_is_left_alone()
	{
		$text = implode("\n\n", [
			'[abc1234](https://example.com) https://github.com/o/r/commit/d5d6321',
			'decade, defaced, 1234567, #ff00ff80, path/93a2f49, 93a2f4, 0123456789abcdef0123456789abcdef012345678',
			'`run 93a2f49`',
			"```\n93a2f49\n```",
			'![3a2f49b](https://example.com/a.png)',
		]);

		$this->assertSame(Markdown::toHtml($text), Markdown::toHtml($text, self::REPO));
		$this->assertStringNotContainsString('/commit/93a2f49', (string) Markdown::toHtml($text, self::REPO));
	}

	public function test_without_a_repository_nothing_changes()
	{
		$this->assertSame("<p>Fixed in 93a2f49</p>\n", Markdown::toHtml('Fixed in 93a2f49'));
		$this->assertSame("<p>Fixed in 93a2f49</p>\n", Markdown::toHtml('Fixed in 93a2f49', ''));
	}
}
