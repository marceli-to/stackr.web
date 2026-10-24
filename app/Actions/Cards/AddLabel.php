<?php

namespace App\Actions\Cards;

use App\Models\Card;
use App\Models\Label;

class AddLabel
{
	public function handle(Card $card, Label $label): void
	{
		$card->labels()->syncWithoutDetaching([$label->id]);
	}
}
