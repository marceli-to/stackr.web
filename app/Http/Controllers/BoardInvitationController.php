<?php

namespace App\Http\Controllers;

use App\Actions\Members\AcceptInvitation;
use App\Actions\Members\WithdrawInvitation;
use App\Models\BoardInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BoardInvitationController extends Controller
{
	/**
	 * Open an invitation link. Guests sign up (or log in) first and come back
	 * here; then the signed-in user joins the board.
	 */
	public function show(Request $request, string $token, AcceptInvitation $acceptInvitation): RedirectResponse
	{
		$invitation = BoardInvitation::with('board')->where('token', $token)->first();

		if ($invitation === null || $invitation->isExpired()) {
			$this->toast(__('This invitation is no longer valid. Ask for a new one.'), 'error');

			return to_route('home');
		}

		/** @var User|null $user */
		$user = $request->user();

		if ($user === null) {
			$request->session()->put('url.intended', $request->fullUrl());

			return User::where('email', $invitation->email)->exists()
				? to_route('login', ['email' => $invitation->email])
				: to_route('register', ['email' => $invitation->email]);
		}

		$board = $acceptInvitation->handle($invitation, $user);

		$this->toast(__('Welcome to :board.', ['board' => $board->name]));

		return to_route('boards.show', $board);
	}

	/**
	 * Withdraw an invitation that hasn't been accepted.
	 */
	public function destroy(BoardInvitation $invitation, WithdrawInvitation $withdrawInvitation): RedirectResponse
	{
		Gate::authorize('manageMembers', $invitation->board);

		$withdrawInvitation->handle($invitation);

		$this->toast(__('Invitation withdrawn.'));

		return back();
	}
}
