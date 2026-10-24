<?php

namespace App\Enums;

enum BoardRole: string
{
	case Owner = 'owner';
	case Member = 'member';
}
