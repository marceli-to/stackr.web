<?php

namespace App\Http\Controllers;

use App\Actions\Boards\SetBackground;
use App\Http\Requests\BackgroundRequest;
use App\Models\Attachment;
use App\Models\Board;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BoardBackgroundController extends Controller
{
	/**
	 * Serve the board's background to its members.
	 */
	public function show(Board $board): StreamedResponse
	{
		Gate::authorize('view', $board);

		abort_if($board->background_path === null, 404);

		return Storage::disk(Attachment::DISK)->response($board->background_path, null, [
			'Cache-Control' => 'private, max-age=31536000, immutable',
			'X-Content-Type-Options' => 'nosniff',
		]);
	}

	/**
	 * Put a photo behind the board.
	 */
	public function store(BackgroundRequest $request, Board $board, SetBackground $setBackground): RedirectResponse
	{
		Gate::authorize('update', $board);

		$setBackground->handle($board, $request->file('image'));

		$this->toast(__('Background updated.'));

		return back();
	}

	/**
	 * Go back to the plain colour.
	 */
	public function destroy(Board $board, SetBackground $setBackground): RedirectResponse
	{
		Gate::authorize('update', $board);

		$setBackground->handle($board, null);

		$this->toast(__('Background removed.'));

		return back();
	}
}
