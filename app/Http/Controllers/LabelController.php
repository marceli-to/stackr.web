<?php

namespace App\Http\Controllers;

use App\Actions\Labels\CreateLabel;
use App\Actions\Labels\DeleteLabel;
use App\Actions\Labels\UpdateLabel;
use App\Http\Requests\LabelRequest;
use App\Models\Label;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class LabelController extends Controller
{
	/**
	 * Add a label to a project's set.
	 */
	public function store(LabelRequest $request, Project $project, CreateLabel $createLabel): RedirectResponse
	{
		Gate::authorize('update', $project);

		$createLabel->handle($project, $request->validated());

		return back();
	}

	/**
	 * Rename or recolour a label on every card that has it.
	 */
	public function update(LabelRequest $request, Label $label, UpdateLabel $updateLabel): RedirectResponse
	{
		Gate::authorize('update', $label->project);

		$updateLabel->handle($label, $request->validated());

		return back();
	}

	/**
	 * Delete a label; it comes off every card.
	 */
	public function destroy(Label $label, DeleteLabel $deleteLabel): RedirectResponse
	{
		Gate::authorize('update', $label->project);

		$deleteLabel->handle($label);

		return back();
	}
}
