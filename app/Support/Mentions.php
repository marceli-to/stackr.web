<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * "@Amy Rossi" in a comment mentions the board member called Amy Rossi.
 * Names are matched whole and case-insensitively; longer names win, so
 * "@Amy Rossi" isn't read as "@Amy" when both are members.
 */
class Mentions
{
	/**
	 * The members the text mentions.
	 *
	 * @param  Collection<int, User>  $members
	 * @return Collection<int, User>
	 */
	public static function in(string $text, Collection $members): Collection
	{
		$found = collect();

		foreach (self::longestFirst($members) as $member) {
			$pattern = self::pattern($member->name);

			if (preg_match($pattern, $text) === 1) {
				$found->push($member);
				$text = (string) preg_replace($pattern, '', $text);
			}
		}

		return $found;
	}

	/**
	 * Wraps the mentions in rendered HTML in <span class="mention">, outside tags only.
	 *
	 * @param  list<string>  $names
	 */
	public static function highlight(string $html, array $names): string
	{
		if ($names === []) {
			return $html;
		}

		usort($names, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));
		$escaped = array_map(fn (string $name): string => preg_quote(e($name), '/'), $names);
		$pattern = '/(?<![\w@])@('.implode('|', $escaped).')(?![\w])/iu';

		$parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];

		return implode('', array_map(
			fn (string $part): string => str_starts_with($part, '<')
				? $part
				: (string) preg_replace($pattern, '<span class="mention">@$1</span>', $part),
			$parts,
		));
	}

	private static function pattern(string $name): string
	{
		return '/(?<![\w@])@'.preg_quote($name, '/').'(?![\w])/iu';
	}

	/**
	 * @param  Collection<int, User>  $members
	 * @return Collection<int, User>
	 */
	private static function longestFirst(Collection $members): Collection
	{
		return $members->sortByDesc(fn (User $member): int => mb_strlen($member->name))->values();
	}
}
