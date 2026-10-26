<?php

namespace App\Http\Controllers;

use App\Actions\Reactions\ToggleReaction;
use App\Http\Requests\ReactionRequest;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReactionController extends Controller
{
	/**
	 * React to a comment with an emoji, or take the reaction back.
	 */
	public function toggle(ReactionRequest $request, Comment $comment, ToggleReaction $toggleReaction): RedirectResponse
	{
		Gate::authorize('update', $comment->card->stack->board);

		/** @var User $user */
		$user = $request->user();

		$toggleReaction->handle($comment, $user, $request->validated('emoji'));

		return back();
	}
}
