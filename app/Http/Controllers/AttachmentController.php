<?php

namespace App\Http\Controllers;

use App\Actions\Attachments\DeleteAttachment;
use App\Actions\Attachments\StoreAttachments;
use App\Http\Requests\AttachmentRequest;
use App\Models\Attachment;
use App\Models\Card;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
	/**
	 * Upload one or more files to a card.
	 */
	public function store(AttachmentRequest $request, Card $card, StoreAttachments $storeAttachments): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		/** @var User $user */
		$user = $request->user();

		$storeAttachments->handle($card, $user, $request->uploads());

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
	public function destroy(Attachment $attachment, DeleteAttachment $deleteAttachment): RedirectResponse
	{
		Gate::authorize('update', $attachment->card->stack->project);

		$deleteAttachment->handle($attachment);

		return back();
	}
}
