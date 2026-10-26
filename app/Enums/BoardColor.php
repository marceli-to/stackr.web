<?php

namespace App\Enums;

/**
 * The colour a board stands on: twelve solid colours, then twelve
 * gradients that blend two of them. A new board without a chosen colour
 * takes the one after the user's latest board.
 */
enum BoardColor: string
{
	case Cobalt = 'cobalt';
	case Rust = 'rust';
	case Moss = 'moss';
	case Oxblood = 'oxblood';
	case Lagoon = 'lagoon';
	case Plum = 'plum';
	case Navy = 'navy';
	case Indigo = 'indigo';
	case Pine = 'pine';
	case Bronze = 'bronze';
	case Slate = 'slate';
	case Graphite = 'graphite';
	case Dusk = 'dusk';
	case Ember = 'ember';
	case Tide = 'tide';
	case Grove = 'grove';
	case Fig = 'fig';
	case Aurora = 'aurora';
	case Dune = 'dune';
	case Dawn = 'dawn';
	case Storm = 'storm';
	case Midnight = 'midnight';
	case Meadow = 'meadow';
	case Violet = 'violet';

	public function next(): self
	{
		$cases = self::cases();
		$index = array_search($this, $cases, true);

		return $cases[($index + 1) % count($cases)];
	}
}
