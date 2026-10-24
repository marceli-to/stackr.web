<?php

namespace App\Http\Controllers;

use App\Actions\Members\DisableInviteLink;
use App\Actions\Members\JoinByLink;
use App\Actions\Members\ResetInviteLink;
use App\Models\Board;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InviteLinkController extends Controller
{
	/**
	 * Open a board's join link. Guests sign up (or log in) first and come
	 * back here; then the signed-in user joins the board.
	 */
	public function show(Request $request, string $token, JoinByLink $joinByLink): RedirectResponse
	{
		$board = Board::where('invite_token', $token)->first();

		if ($board === null) {
			$this->toast(__('This invitation is no longer valid. Ask for a new one.'), 'error');

			return to_route('home');
		}

		/** @var User|null $user */
		$user = $request->user();

		if ($user === null) {
			$request->session()->put('url.intended', $request->fullUrl());

			return to_route('register');
		}

		$joinByLink->handle($board, $user);

		$this->toast(__('Welcome to :board.', ['board' => $board->name]));

		return to_route('boards.show', $board);
	}

	/**
	 * Turn the join link on, or replace it with a new one.
	 */
	public function store(Board $board, ResetInviteLink $resetInviteLink): RedirectResponse
	{
		Gate::authorize('manageMembers', $board);

		$hadLink = $board->invite_token !== null;

		$resetInviteLink->handle($board);

		$this->toast($hadLink ? __('New link created; the old one no longer works.') : __('Invite link created.'));

		return back();
	}

	/**
	 * Turn the join link off.
	 */
	public function destroy(Board $board, DisableInviteLink $disableInviteLink): RedirectResponse
	{
		Gate::authorize('manageMembers', $board);

		$disableInviteLink->handle($board);

		$this->toast(__('Invite link turned off.'));

		return back();
	}
}
