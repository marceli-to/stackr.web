<?php

namespace App\Http\Controllers;

use App\Actions\Cards\ArchiveCard;
use App\Actions\Cards\CopyCard;
use App\Actions\Cards\CreateCard;
use App\Actions\Cards\DeleteCard;
use App\Actions\Cards\MoveCard;
use App\Actions\Cards\RestoreCard;
use App\Actions\Cards\UpdateCard;
use App\Http\Requests\CardCopyRequest;
use App\Http\Requests\CardMoveRequest;
use App\Http\Requests\CardRequest;
use App\Http\Requests\CardUpdateRequest;
use App\Models\Card;
use App\Models\Stack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CardController extends Controller
{
	/**
	 * Add a card to the bottom of a list.
	 */
	public function store(CardRequest $request, Stack $stack, CreateCard $createCard): RedirectResponse
	{
		Gate::authorize('update', $stack->project);

		$createCard->handle($stack, $request->validated('title'));

		return back();
	}

	/**
	 * Move a card within its list, into another list, or onto another of the
	 * user's boards. A card that leaves the board also leaves the dialog.
	 */
	public function move(CardMoveRequest $request, Card $card, MoveCard $moveCard): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);

		$target = $request->has('stack_id')
			? Stack::query()->findOrFail($request->integer('stack_id'))
			: $card->stack;

		Gate::authorize('update', $target->project);

		$moveCard->handle($card, $target, $request->integer('index'));

		if ($target->project_id !== $project->id) {
			$this->toast(__('Card moved to :project.', ['project' => $target->project->name]));

			return to_route('projects.show', $project);
		}

		return back();
	}

	/**
	 * Copy a card into a list on any of the user's boards.
	 */
	public function copy(CardCopyRequest $request, Card $card, CopyCard $copyCard): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$target = Stack::query()->findOrFail($request->integer('stack_id'));

		Gate::authorize('update', $target->project);

		/** @var list<string> $keep */
		$keep = $request->validated('keep', []);

		$copyCard->handle($card, $target, $request->integer('index'), $request->validated('title'), $keep);

		$this->toast($target->project_id === $card->stack->project_id
			? __('Card copied.')
			: __('Card copied to :project.', ['project' => $target->project->name]));

		return back();
	}

	/**
	 * Change a card's title, description, dates, completion or cover.
	 */
	public function update(CardUpdateRequest $request, Card $card, UpdateCard $updateCard): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$updateCard->handle($card, $request->validated());

		return back();
	}

	/**
	 * Take a card off the board. It stays open, marked as archived.
	 */
	public function archive(Card $card, ArchiveCard $archiveCard): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$archiveCard->handle($card);

		$this->toast(__('Card archived.'));

		return back();
	}

	/**
	 * Put an archived card back on the board, at its old place.
	 */
	public function restore(Card $card, RestoreCard $restoreCard): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$restoreCard->handle($card);

		$this->toast(__('Card restored.'));

		return back();
	}

	/**
	 * Delete a card with everything on it, and close it.
	 */
	public function destroy(Card $card, DeleteCard $deleteCard): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);

		$deleteCard->handle($card);

		$this->toast(__('Card deleted.'));

		return to_route('projects.show', $project);
	}
}
