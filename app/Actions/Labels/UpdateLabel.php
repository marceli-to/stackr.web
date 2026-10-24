<?php

namespace App\Actions\Labels;

use App\Models\Label;

class UpdateLabel
{
	/**
	 * Renames or recolours the label on every card that has it.
	 *
	 * @param  array<string, mixed>  $data
	 */
	public function handle(Label $label, array $data): void
	{
		$label->update($data);
	}
}
