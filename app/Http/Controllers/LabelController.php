<?php

namespace App\Http\Controllers;

use App\Actions\Labels\CreateLabel;
use App\Actions\Labels\DeleteLabel;
use App\Actions\Labels\UpdateLabel;
use App\Http\Requests\LabelRequest;
use App\Models\Board;
use App\Models\Label;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
	/**
	 * Add a label to a board's set.
	 */
	public function store(LabelRequest $request, Board $board, CreateLabel $createLabel): RedirectResponse
	{
		Gate::authorize('update', $board);

		$createLabel->handle($board, $request->validated());

		return back();
	}

	/**
	 * Rename or recolour a label on every card that has it.
	 */
	public function update(LabelRequest $request, Label $label, UpdateLabel $updateLabel): RedirectResponse
	{
		Gate::authorize('update', $label->board);

		$updateLabel->handle($label, $request->validated());

		return back();
	}

	/**
	 * Delete a label; it comes off every card.
	 */
	public function destroy(Label $label, DeleteLabel $deleteLabel): RedirectResponse
	{
		Gate::authorize('update', $label->board);

		$deleteLabel->handle($label);

		return back();
	}
}
