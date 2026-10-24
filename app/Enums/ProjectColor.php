<?php

namespace App\Enums;

/**
 * The colour a project's board stands on: six solid colours, then five
 * gradients that blend two of them. A new project without a chosen colour
 * takes the one after the user's latest project.
 */
enum ProjectColor: string
{
	case Cobalt = 'cobalt';
	case Rust = 'rust';
	case Moss = 'moss';
	case Oxblood = 'oxblood';
	case Lagoon = 'lagoon';
	case Plum = 'plum';
	case Dusk = 'dusk';
	case Ember = 'ember';
	case Tide = 'tide';
	case Grove = 'grove';
	case Fig = 'fig';

	public function next(): self
	{
		$cases = self::cases();
		$index = array_search($this, $cases, true);

		return $cases[($index + 1) % count($cases)];
	}
}
