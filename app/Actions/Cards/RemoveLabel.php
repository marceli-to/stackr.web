<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Label;

class RemoveLabel
{
	public function handle(Card $card, Label $label): void
	{
		$card->labels()->detach($label->id);
	}
}
