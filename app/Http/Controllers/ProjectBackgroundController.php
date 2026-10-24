<?php

namespace App\Http\Controllers;

use App\Actions\Projects\SetBackground;
use App\Http\Requests\BackgroundRequest;
use App\Models\Attachment;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectBackgroundController extends Controller
{
	/**
	 * Serve the board's background to its members.
	 */
	public function show(Project $project): StreamedResponse
	{
		Gate::authorize('view', $project);

		abort_if($project->background_path === null, 404);

		return Storage::disk(Attachment::DISK)->response($project->background_path, null, [
			'Cache-Control' => 'private, max-age=31536000, immutable',
			'X-Content-Type-Options' => 'nosniff',
		]);
	}

	/**
	 * Put a photo behind the board.
	 */
	public function store(BackgroundRequest $request, Project $project, SetBackground $setBackground): RedirectResponse
	{
		Gate::authorize('update', $project);

		$setBackground->handle($project, $request->file('image'));

		$this->toast(__('Background updated.'));

		return back();
	}

	/**
	 * Go back to the plain colour.
	 */
	public function destroy(Project $project, SetBackground $setBackground): RedirectResponse
	{
		Gate::authorize('update', $project);

		$setBackground->handle($project, null);

		$this->toast(__('Background removed.'));

		return back();
	}
}
