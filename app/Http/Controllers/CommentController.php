<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
	/**
	 * Comment on a card.
	 */
	public function store(Request $request, Card $card): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$comment = new Comment($request->validate([
			'body' => ['required', 'string', 'max:10000'],
		]));
		$comment->card()->associate($card);
		$comment->user()->associate($request->user());
		$comment->save();

		return back();
	}

	/**
	 * Edit your own comment.
	 */
	public function update(Request $request, Comment $comment): RedirectResponse
	{
		Gate::authorize('update', $comment->card->stack->project);
		abort_unless($comment->user_id === $request->user()?->id, 403);

		$comment->update($request->validate([
			'body' => ['required', 'string', 'max:10000'],
		]));

		return back();
	}

	/**
	 * Delete your own comment.
	 */
	public function destroy(Request $request, Comment $comment): RedirectResponse
	{
		Gate::authorize('update', $comment->card->stack->project);
		abort_unless($comment->user_id === $request->user()?->id, 403);

		$comment->delete();

		return back();
	}
}
