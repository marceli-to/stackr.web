<?php

namespace App\Http\Controllers;

use App\Actions\Cards\AssignMember;
use App\Actions\Cards\UnassignMember;
use App\Models\Card;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CardMemberController extends Controller
{
	/**
	 * Assign a project member to a card.
	 */
	public function store(Card $card, User $user, AssignMember $assignMember): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);
		abort_if($project->roleOf($user) === null, 404);

		$assignMember->handle($card, $user);

		return back();
	}

	/**
	 * Take a member off a card.
	 */
	public function destroy(Card $card, User $user, UnassignMember $unassignMember): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$unassignMember->handle($card, $user);

		return back();
	}
}
