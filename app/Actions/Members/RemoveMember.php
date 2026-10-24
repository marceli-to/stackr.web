<?php

namespace App\Actions\Members;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemoveMember
{
	/**
	 * Takes the user off the project, off its cards and their watch lists.
	 */
	public function handle(Project $project, User $user): void
	{
		DB::transaction(function () use ($project, $user): void {
			$cards = $project->cards()->pluck('cards.id');

			DB::table('card_user')->where('user_id', $user->id)->whereIn('card_id', $cards)->delete();
			DB::table('card_watchers')->where('user_id', $user->id)->whereIn('card_id', $cards)->delete();
			$project->watchers()->detach($user);
			$project->members()->detach($user);
		});
	}
}
