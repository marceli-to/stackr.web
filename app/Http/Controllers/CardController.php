<?php

namespace App\Http\Controllers;

use App\Http\Requests\CardRequest;
use App\Http\Requests\CardUpdateRequest;
use App\Http\Requests\MoveRequest;
use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class CardController extends Controller
{
	/**
	 * Add a card to the bottom of a list.
	 */
	public function store(CardRequest $request, Stack $stack): RedirectResponse
	{
		Gate::authorize('update', $stack->project);

		DB::transaction(function () use ($request, $stack): void {
			$last = $stack->cards()->reorder()->max('position');

			$stack->cards()->create([
				...$request->validated(),
				'position' => FractionalIndex::between($last, null),
			]);
		});

		return back();
	}

	/**
	 * Move a card within its list or into another list of the same project.
	 */
	public function move(MoveRequest $request, Card $card): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);

		$target = $request->has('stack_id')
			? $project->stacks()->find($request->integer('stack_id'))
			: $card->stack;

		if ($target === null) {
			throw ValidationException::withMessages([
				'stack_id' => __('The list does not belong to this project.'),
			]);
		}

		DB::transaction(function () use ($request, $card, $target): void {
			$siblings = $target->cards()
				->whereKeyNot($card->id)
				->get(['id', 'position'])
				->map(fn (Card $sibling): string => $sibling->position)
				->all();

			$card->stack()->associate($target);
			$card->position = FractionalIndex::forIndex($siblings, $request->integer('index'));
			$card->save();
		});

		return back();
	}

	/**
	 * Change a card's title, description, dates, completion or cover.
	 */
	public function update(CardUpdateRequest $request, Card $card): RedirectResponse
	{
		Gate::authorize('update', $card->stack->project);

		$data = $request->safe()->except('completed');

		if ($request->has('completed')) {
			$data['completed_at'] = $request->boolean('completed') ? ($card->completed_at ?? now()) : null;
		}

		$card->update($data);

		return back();
	}

	/**
	 * Delete a card with everything on it, and close it.
	 */
	public function destroy(Card $card): RedirectResponse
	{
		$project = $card->stack->project;

		Gate::authorize('update', $project);

		$card->delete();

		Inertia::flash('toast', ['type' => 'success', 'message' => __('Card deleted.')]);

		return to_route('projects.show', $project);
	}
}
