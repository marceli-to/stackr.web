<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Card;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
	/**
	 * Upload one or more files to a card. The first image becomes the cover
	 * when the card has none yet, as on Trello.
	 */
	public function store(Request $request, Card $card): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);

		$request->validate([
			'files' => ['required', 'array', 'max:20'],
			'files.*' => ['file', 'max:20480'],
		]);

		$directory = Attachment::directoryFor($project->id, $card->id);

		/** @var list<UploadedFile> $files */
		$files = $request->file('files');

		foreach ($files as $file) {
			$extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
			$path = $file->storeAs($directory, Str::uuid().($extension !== '' ? ".{$extension}" : ''), Attachment::DISK);
			$size = str_starts_with((string) $file->getMimeType(), 'image/') ? @getimagesize($file->getRealPath()) : false;

			$attachment = new Attachment([
				'path' => $path,
				'name' => Str::limit($file->getClientOriginalName(), 250, ''),
				'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
				'size' => $file->getSize(),
				'width' => $size !== false ? $size[0] : null,
				'height' => $size !== false ? $size[1] : null,
			]);
			$attachment->card()->associate($card);
			$attachment->user()->associate($request->user());
			$attachment->save();

			if ($card->cover_attachment_id === null && $attachment->isImage()) {
				$card->update(['cover_attachment_id' => $attachment->id]);
			}
		}

		return back();
	}

	/**
	 * Serve a file to project members. Images and PDFs open in the browser;
	 * ?download=1 saves them instead.
	 */
	public function show(Request $request, Attachment $attachment): StreamedResponse
	{
		Gate::authorize('view', $attachment->card->stack->project);

		$inline = ! $request->boolean('download')
			&& ($attachment->isImage() || $attachment->mime_type === 'application/pdf');

		$disk = Storage::disk(Attachment::DISK);
		$headers = [
			'Content-Type' => $attachment->mime_type,
			'Cache-Control' => 'private, max-age=31536000, immutable',
			'X-Content-Type-Options' => 'nosniff',
		];

		return $inline
			? $disk->response($attachment->path, $attachment->name, $headers)
			: $disk->download($attachment->path, $attachment->name, $headers);
	}

	/**
	 * Delete a file. A card that used it as its cover loses the cover.
	 */
	public function destroy(Attachment $attachment): RedirectResponse
	{
		Gate::authorize('update', $attachment->card->stack->project);

		$attachment->delete();

		return back();
	}
}
