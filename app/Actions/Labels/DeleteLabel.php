<?php

namespace App\Actions\Labels;

use App\Models\Label;

class DeleteLabel
{
	/**
	 * Deletes the label; it comes off every card.
	 */
	public function handle(Label $label): void
	{
		$label->delete();
	}
}
