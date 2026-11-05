<?php

namespace App\Http\Controllers;

use App\Http\Resources\MyCardResource;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyCardsController extends Controller
{
	/**
	 * Every active card the user is on, across their boards: soonest due
	 * first, cards without a due date last, then by board and list order.
	 */
	public function index(Request $request): Response
	{
		/** @var User $user */
		$user = $request->user();

		$cards = Card::query()
			->active()
			->whereHas('members', fn (Builder $query) => $query->whereKey($user->id))
			->whereHas('stack', fn (Builder $query) => $query
				->whereNull('archived_at')
				->whereIn('board_id', $user->boards()->select('boards.id')))
			->with(['stack.board', 'labels', 'checklists.items:id,checklist_id,completed_at'])
			->get()
			->sort(fn (Card $a, Card $b): int => ($a->due_at === null) <=> ($b->due_at === null)
				?: $a->due_at?->getTimestamp() <=> $b->due_at?->getTimestamp()
				?: strnatcasecmp($a->stack->board->name, $b->stack->board->name)
				?: strcmp($a->stack->position, $b->stack->position)
				?: strcmp($a->position, $b->position))
			->values();

		return Inertia::render('cards/Mine', [
			'cards' => MyCardResource::collection($cards),
		]);
	}
}
