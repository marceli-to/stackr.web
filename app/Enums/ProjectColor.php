<?php

namespace App\Enums;

/**
 * The colour a project's board stands on. A new project without a chosen
 * colour takes the one after the user's latest project.
 */
enum ProjectColor: string
{
	case Cobalt = 'cobalt';
	case Rust = 'rust';
	case Moss = 'moss';
	case Oxblood = 'oxblood';
	case Lagoon = 'lagoon';
	case Plum = 'plum';

	public function next(): self
	{
		$cases = self::cases();
		$index = array_search($this, $cases, true);

		return $cases[($index + 1) % count($cases)];
	}
}
