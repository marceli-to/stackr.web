<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\NotificationSettingsRequest;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
	/**
	 * Show the user's profile settings page.
	 */
	public function edit(Request $request): Response
	{
		return Inertia::render('settings/Profile', [
			'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
			'status' => $request->session()->get('status'),
		]);
	}

	/**
	 * Update the user's profile information.
	 */
	public function update(ProfileUpdateRequest $request): RedirectResponse
	{
		$request->user()->fill($request->validated());

		if ($request->user()->isDirty('email')) {
			$request->user()->email_verified_at = null;
		}

		$request->user()->save();

		$this->toast(__('Profile updated.'));

		return to_route('profile.edit');
	}

	/**
	 * Turn notification emails on or off.
	 */
	public function notifications(NotificationSettingsRequest $request): RedirectResponse
	{
		$request->user()->update(['email_notifications' => $request->boolean('email_notifications')]);

		$this->toast(__('Notification settings saved.'));

		return back();
	}

	/**
	 * Delete the user's profile.
	 */
	public function destroy(ProfileDeleteRequest $request): RedirectResponse
	{
		$user = $request->user();

		Auth::logout();

		$user->delete();

		$request->session()->invalidate();
		$request->session()->regenerateToken();

		return redirect('/');
	}
}
