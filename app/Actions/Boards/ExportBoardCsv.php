<?php

namespace App\Actions\Boards;

use App\Models\Board;
use App\Models\Card;
use App\Models\ChecklistItem;
use App\Models\Label;
use App\Models\Stack;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExportBoardCsv
{
	/** @var list<string> */
	public const COLUMNS = [
		'List', 'Title', 'Description', 'Labels', 'Members', 'Start', 'Due', 'Completed',
		'Checklist', 'Comments', 'Attachments', 'Created', 'Updated', 'Link',
	];

	/**
	 * One row per active card, in board order, for opening in a spreadsheet.
	 * Archived lists and cards stay out, as in the ZIP export.
	 */
	public function handle(Board $board): string
	{
		$board->load([
			'stacks' => fn (HasMany $query) => $query->active(),
			'stacks.cards' => fn (HasMany $query) => $query->active()->withCount(['comments', 'attachments']),
			'stacks.cards.labels',
			'stacks.cards.members',
			'stacks.cards.checklists.items:id,checklist_id,completed_at',
		]);

		$handle = fopen('php://temp', 'r+');

		if ($handle === false) {
			throw new \RuntimeException('Could not create the export file.');
		}

		// A byte order mark, so Excel reads the file as UTF-8.
		fwrite($handle, "\u{FEFF}");
		fputcsv($handle, self::COLUMNS, escape: '');

		foreach ($board->stacks as $stack) {
			foreach ($stack->cards as $card) {
				fputcsv($handle, array_map($this->cell(...), $this->row($board, $stack, $card)), escape: '');
			}
		}

		rewind($handle);
		$csv = (string) stream_get_contents($handle);
		fclose($handle);

		return $csv;
	}

	/**
	 * @return list<string|int>
	 */
	private function row(Board $board, Stack $stack, Card $card): array
	{
		$items = $card->checklists->flatMap->items;

		return [
			$stack->name,
			$card->title,
			$card->description ?? '',
			$card->labels->map(fn (Label $label): string => $label->name ?? $label->color->value)->implode(', '),
			$card->members->map(fn (User $user): string => $user->name)->implode(', '),
			$this->date($card->start_at),
			$this->date($card->due_at),
			$this->date($card->completed_at),
			$items->isEmpty() ? '' : $items->filter(fn (ChecklistItem $item): bool => $item->completed_at !== null)->count().'/'.$items->count(),
			(int) $card->comments_count,
			(int) $card->attachments_count,
			$this->date($card->created_at),
			$this->date($card->updated_at),
			route('boards.show', [$board, 'card' => $card->id]),
		];
	}

	private function date(?CarbonInterface $date): string
	{
		return $date?->format('Y-m-d H:i') ?? '';
	}

	/**
	 * Text that a spreadsheet would run as a formula gets a leading quote.
	 */
	private function cell(string|int $value): string|int
	{
		return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
	}
}
