<?php

namespace App\Http\Controllers;

use App\Actions\Cards\ArchiveCard;
use App\Actions\Cards\CreateCard;
use App\Actions\Cards\DeleteCard;
use App\Actions\Cards\MoveCard;
use App\Actions\Cards\RestoreCard;
use App\Actions\Cards\UpdateCard;
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
	 * Move a card within its list or into another list of the same project.
	 */
	public function move(CardMoveRequest $request, Card $card, MoveCard $moveCard): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);

		$target = $request->has('stack_id')
			? $project->stacks()->findOrFail($request->integer('stack_id'))
			: $card->stack;

		$moveCard->handle($card, $target, $request->integer('index'));

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
