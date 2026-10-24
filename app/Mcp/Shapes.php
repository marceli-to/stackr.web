<?php

namespace App\Mcp;

use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Stack;
use App\Models\User;

/**
 * What the agent reads: plain arrays with the ids it needs for the next
 * call, and dates as ISO 8601.
 */
class Shapes
{
	/**
	 * @return array<string, mixed>
	 */
	public static function summary(Board $board): array
	{
		return [
			'key' => $board->key,
			'name' => $board->name,
			'description' => $board->description,
			'url' => route('boards.show', $board),
		];
	}

	/**
	 * The board's lists with their cards, plus the labels and members a
	 * card can be given.
	 *
	 * @return array<string, mixed>
	 */
	public static function board(Board $board): array
	{
		$stacks = $board->stacks()
			->active()
			->with(['cards' => fn ($query) => $query
				->active()
				->where('is_template', false)
				->with(['labels', 'members', 'checklists.items'])
				->withCount('comments')])
			->get();

		return [
			...self::summary($board),
			'lists' => $stacks->map(fn (Stack $stack): array => [
				'id' => $stack->id,
				'name' => $stack->name,
				'wip_limit' => $stack->wip_limit,
				'cards' => $stack->cards->map(fn (Card $card): array => self::face($card))->all(),
			])->all(),
			'labels' => $board->labels()->get()->map(fn (Label $label): array => self::label($label))->all(),
			'members' => $board->members()->orderBy('name')->get()->map(fn (User $user): array => self::member($user))->all(),
		];
	}

	/**
	 * A card as it shows on the board.
	 *
	 * @return array<string, mixed>
	 */
	public static function face(Card $card): array
	{
		$items = $card->checklists->flatMap(fn (Checklist $checklist) => $checklist->items);

		return [
			'id' => $card->id,
			'title' => $card->title,
			'due_at' => $card->due_at?->toIso8601String(),
			'completed' => $card->completed_at !== null,
			'labels' => $card->labels->map(fn (Label $label): string => $label->name ?? $label->color->value)->all(),
			'members' => $card->members->pluck('name')->all(),
			'checklist' => $items->isEmpty() ? null : $items->whereNotNull('completed_at')->count().'/'.$items->count(),
			'comments' => $card->comments_count ?? 0,
		];
	}

	/**
	 * Everything on a card, with the latest comments first.
	 *
	 * @return array<string, mixed>
	 */
	public static function card(Card $card): array
	{
		$card->load(['stack.board', 'labels', 'members', 'checklists.items']);
		$board = $card->stack->board;

		return [
			'id' => $card->id,
			'title' => $card->title,
			'description' => $card->description,
			'board' => ['key' => $board->key, 'name' => $board->name],
			'list' => ['id' => $card->stack->id, 'name' => $card->stack->name],
			'start_at' => $card->start_at?->toIso8601String(),
			'due_at' => $card->due_at?->toIso8601String(),
			'completed' => $card->completed_at !== null,
			'labels' => $card->labels->map(fn (Label $label): array => self::label($label))->all(),
			'members' => $card->members->map(fn (User $user): array => self::member($user))->all(),
			'checklists' => $card->checklists->map(fn (Checklist $checklist): array => [
				'id' => $checklist->id,
				'title' => $checklist->title,
				'items' => $checklist->items->map(fn (ChecklistItem $item): array => [
					'id' => $item->id,
					'title' => $item->title,
					'done' => $item->completed_at !== null,
				])->all(),
			])->all(),
			'comments' => $card->comments()->with('user')->limit(50)->get()
				->map(fn (Comment $comment): array => [
					'id' => $comment->id,
					'author' => $comment->user?->name,
					'body' => $comment->body,
					'via_agent' => $comment->via_agent,
					'created_at' => $comment->created_at?->toIso8601String(),
				])->all(),
			'url' => route('boards.show', [$board, 'card' => $card->id]),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function label(Label $label): array
	{
		return ['id' => $label->id, 'name' => $label->name, 'color' => $label->color->value];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function member(User $user): array
	{
		return ['id' => $user->id, 'name' => $user->name, 'email' => $user->email];
	}
}
