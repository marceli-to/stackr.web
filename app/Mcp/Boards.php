<?php

namespace App\Mcp;

use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Label;
use App\Models\Project;
use App\Models\ProjectMembership;
use App\Models\Stack;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;

/**
 * Finds what an agent asks for, only on the boards its user is a member of,
 * and checks the same policy as the web app. Anything else reads as not
 * found, so a token can't tell other boards exist.
 */
class Boards
{
	/**
	 * The token's user; the route only lets signed-in requests through.
	 */
	public function user(Request $request): User
	{
		$user = $request->user();

		if (! $user instanceof User) {
			throw new AuthenticationException;
		}

		return $user;
	}

	/**
	 * A project by its key (from list_projects) or its exact name.
	 */
	public function project(User $user, string $project, string $ability = 'update'): Project
	{
		$found = $user->projects()->where('projects.key', $project)->first()
			?? $this->byName($user->projects()->get(), $project, 'project', 'name');

		Gate::authorize($ability, $found);

		return $found;
	}

	/**
	 * A card on one of the user's boards; archived cards, cards in archived
	 * lists and templates don't count.
	 */
	public function card(User $user, int $id, string $ability = 'update'): Card
	{
		$card = Card::query()
			->active()
			->where('is_template', false)
			->whereHas('stack', fn (Builder $query) => $query->active()->whereIn('project_id', $this->projectIds($user)))
			->find($id) ?? $this->notFound('card_id', "There's no card {$id} on your boards.");

		Gate::authorize($ability, $card->stack->project);

		return $card;
	}

	/**
	 * A list on the board, by id or by name (any case). A list id from
	 * another of the user's boards works too, for moving a card across.
	 */
	public function stack(User $user, Project $project, int|string $list): Stack
	{
		if (is_int($list) || ctype_digit($list)) {
			$stack = Stack::query()
				->active()
				->whereIn('project_id', $this->projectIds($user))
				->find((int) $list) ?? $this->notFound('list', "There's no list {$list} on your boards.");

			Gate::authorize('update', $stack->project);

			return $stack;
		}

		return $this->byName($project->stacks()->active()->get(), $list, 'list', 'name');
	}

	public function checklist(User $user, int $id): Checklist
	{
		$checklist = Checklist::query()->find($id);

		if ($checklist === null || ! $this->reaches($user, $checklist->card)) {
			$this->notFound('checklist_id', "There's no checklist {$id} on your boards.");
		}

		return $checklist;
	}

	public function checklistItem(User $user, int $id): ChecklistItem
	{
		$item = ChecklistItem::query()->find($id);

		if ($item === null || ! $this->reaches($user, $item->checklist->card)) {
			$this->notFound('item_id', "There's no checklist item {$id} on your boards.");
		}

		return $item;
	}

	/**
	 * A member of the board, by id, email or name (any case).
	 */
	public function member(Project $project, int|string $member): User
	{
		$members = $project->members()->get();

		$found = $members->first(fn (User $user): bool => (string) $user->id === (string) $member
			|| Str::lower($user->email) === Str::lower((string) $member));

		return $found ?? $this->byName($members, (string) $member, 'member', 'name');
	}

	/**
	 * One of the board's labels, by id or name (any case).
	 */
	public function label(Project $project, int|string $label): Label
	{
		$labels = $project->labels()->get();

		return $labels->first(fn (Label $found): bool => (string) $found->id === (string) $label)
			?? $this->byName($labels->whereNotNull('name'), (string) $label, 'label', 'name');
	}

	/**
	 * The user's project ids, as a subquery.
	 *
	 * @return HasMany<ProjectMembership, User>
	 */
	private function projectIds(User $user): HasMany
	{
		return $user->memberships()->select('project_id');
	}

	/**
	 * Whether the card is one the agent may change: same rules as card().
	 */
	private function reaches(User $user, Card $card): bool
	{
		try {
			$this->card($user, $card->id);
		} catch (ValidationException) {
			return false;
		}

		return true;
	}

	/**
	 * @template TModel of Model
	 *
	 * @param  Collection<int, TModel>  $models
	 * @return TModel
	 */
	private function byName(Collection $models, string $name, string $field, string $attribute): Model
	{
		$named = fn (Model $model): bool => Str::lower(trim((string) $model->getAttribute($attribute))) === Str::lower(trim($name));

		if ($models->filter($named)->count() > 1) {
			$this->notFound($field, "More than one {$field} is called \"{$name}\". Use its id instead.");
		}

		return $models->first($named) ?? $this->notFound($field, "There's no {$field} called \"{$name}\".");
	}

	private function notFound(string $field, string $message): never
	{
		throw ValidationException::withMessages([$field => $message]);
	}
}
