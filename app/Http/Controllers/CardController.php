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
		Gate::authorize('update', $stack->board);

		$createCard->handle($stack, $request->validated('title'));

		return back();
	}

	/**
	 * Start a card from one of the board's templates, at the bottom of a list,
	 * and open it.
	 */
	public function fromTemplate(Stack $stack, Card $card, CopyCard $copyCard): RedirectResponse
	{
		Gate::authorize('update', $stack->board);

		abort_unless($card->is_template && $card->stack->board_id === $stack->board_id, 404);

		$copy = $copyCard->handle(
			$card,
			$stack,
			$stack->cards()->active()->count(),
			$card->title,
			['checklists', 'labels', 'members', 'attachments'],
		);

		return to_route('boards.show', [$stack->board, 'card' => $copy->id]);
	}

	/**
	 * Move a card within its list, into another list, or onto another of the
	 * user's boards. A card that leaves the board also leaves the dialog.
	 */
	public function move(CardMoveRequest $request, Card $card, MoveCard $moveCard): RedirectResponse
	{
		$board = $card->stack->board;

		Gate::authorize('update', $board);

		$target = $request->has('stack_id')
			? Stack::query()->findOrFail($request->integer('stack_id'))
			: $card->stack;

		Gate::authorize('update', $target->board);

		$moveCard->handle($card, $target, $request->integer('index'));

		if ($target->board_id !== $board->id) {
			$this->toast(__('Card moved to :board.', ['board' => $target->board->name]));

			return to_route('boards.show', $board);
		}

		return back();
	}

	/**
	 * Copy a card into a list on any of the user's boards.
	 */
	public function copy(CardCopyRequest $request, Card $card, CopyCard $copyCard): RedirectResponse
	{
		Gate::authorize('update', $card->stack->board);

		$target = Stack::query()->findOrFail($request->integer('stack_id'));

		Gate::authorize('update', $target->board);

		/** @var list<string> $keep */
		$keep = $request->validated('keep', []);

		$copyCard->handle($card, $target, $request->integer('index'), $request->validated('title'), $keep);

		$this->toast($target->board_id === $card->stack->board_id
			? __('Card copied.')
			: __('Card copied to :board.', ['board' => $target->board->name]));

		return back();
	}

	/**
	 * Change a card's title, description, dates, completion or cover.
	 */
	public function update(CardUpdateRequest $request, Card $card, UpdateCard $updateCard): RedirectResponse
	{
		Gate::authorize('update', $card->stack->board);

		$updateCard->handle($card, $request->validated());

		return back();
	}

	/**
	 * Take a card off the board. It stays open, marked as archived.
	 */
	public function archive(Card $card, ArchiveCard $archiveCard): RedirectResponse
	{
		Gate::authorize('update', $card->stack->board);

		$archiveCard->handle($card);

		$this->toast(__('Card archived.'));

		return back();
	}

	/**
	 * Put an archived card back on the board, at its old place.
	 */
	public function restore(Card $card, RestoreCard $restoreCard): RedirectResponse
	{
		Gate::authorize('update', $card->stack->board);

		$restoreCard->handle($card);

		$this->toast(__('Card restored.'));

		return back();
	}

	/**
	 * Delete a card with everything on it, and close it.
	 */
	public function destroy(Card $card, DeleteCard $deleteCard): RedirectResponse
	{
		$board = $card->stack->board;

		Gate::authorize('update', $board);

		$deleteCard->handle($card);

		$this->toast(__('Card deleted.'));

		return to_route('boards.show', $board);
	}
}
