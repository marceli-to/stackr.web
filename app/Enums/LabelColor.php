<?php

namespace App\Enums;

/**
 * The colours a label can have. They sit on white cards, so they are mid
 * tones that carry dark text.
 */
enum LabelColor: string
{
	case Green = 'green';
	case Yellow = 'yellow';
	case Orange = 'orange';
	case Red = 'red';
	case Purple = 'purple';
	case Blue = 'blue';
	case Sky = 'sky';
	case Gray = 'gray';
}
