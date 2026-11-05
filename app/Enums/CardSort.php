<?php

namespace App\Enums;

/**
 * The orders a list's cards can be sorted into, once, from the list menu.
 */
enum CardSort: string
{
	case Due = 'due';
	case Newest = 'newest';
	case Oldest = 'oldest';
	case Title = 'title';
}
