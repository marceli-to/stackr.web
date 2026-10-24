<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CardMemberController extends Controller
{
	/**
	 * Assign a project member to a card.
	 */
	public function store(Card $card, User $user): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);
		abort_if($project->roleOf($user) === null, 404);

		$card->members()->syncWithoutDetaching([$user->id]);

		return back();
	}

	/**
	 * Take a member off a card.
	 */
	public function destroy(Card $card, User $user): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$card->members()->detach($user->id);

		return back();
	}
}
