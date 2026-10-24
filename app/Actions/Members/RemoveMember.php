<?php

namespace App\Actions\Members;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemoveMember
{
	/**
	 * Takes the user off the project and off its cards.
	 */
	public function handle(Project $project, User $user): void
	{
		DB::transaction(function () use ($project, $user): void {
			$cards = $project->cards()->pluck('cards.id');

			DB::table('card_user')->where('user_id', $user->id)->whereIn('card_id', $cards)->delete();
			$project->members()->detach($user);
		});
	}
}
