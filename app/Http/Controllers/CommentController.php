<?php

namespace App\Http\Controllers;

use App\Actions\Comments\CreateComment;
use App\Actions\Comments\DeleteComment;
use App\Actions\Comments\UpdateComment;
use App\Http\Requests\CommentRequest;
use App\Models\Card;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
	/**
	 * Comment on a card.
	 */
	public function store(CommentRequest $request, Card $card, CreateComment $createComment): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		/** @var User $user */
		$user = $request->user();

		$createComment->handle($card, $user, $request->validated('body'));

		return back();
	}

	/**
	 * Edit your own comment.
	 */
	public function update(CommentRequest $request, Comment $comment, UpdateComment $updateComment): RedirectResponse
	{
		Gate::authorize('update', $comment->card->stack->project);
		abort_unless($comment->user_id === $request->user()?->id, 403);

		$updateComment->handle($comment, $request->validated('body'));

		return back();
	}

	/**
	 * Delete your own comment.
	 */
	public function destroy(Request $request, Comment $comment, DeleteComment $deleteComment): RedirectResponse
	{
		Gate::authorize('update', $comment->card->stack->project);
		abort_unless($comment->user_id === $request->user()?->id, 403);

		$deleteComment->handle($comment);

		return back();
	}
}
