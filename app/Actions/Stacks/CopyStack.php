<?php

namespace App\Actions\Stacks;

use App\Actions\Cards\CopyCard;
use App\Models\Card;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;

class CopyStack
{
	public function __construct(private CopyCard $copyCard) {}

	/**
	 * Puts a copy of the list right after it, with copies of its cards (the
	 * ones on the board, in order) and everything on them but comments.
	 * Templates stay templates; nobody is notified about the copies.
	 */
	public function handle(Stack $stack, string $name): Stack
	{
		return DB::transaction(function () use ($stack, $name): Stack {
			$next = $stack->board->stacks()
				->reorder()
				->where('position', '>', $stack->position)
				->min('position');

			$copy = $stack->board->stacks()->create([
				'name' => $name,
				'position' => FractionalIndex::between($stack->position, $next),
				'wip_limit' => $stack->wip_limit,
			]);

			$stack->cards()->active()->get()->each(fn (Card $card, int $index) => $this->copyCard->handle(
				$card,
				$copy,
				$index,
				$card->title,
				['checklists', 'labels', 'members', 'attachments'],
				asIs: true,
			));

			return $copy;
		});
	}
}
