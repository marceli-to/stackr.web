<?php

namespace App\Actions\Cards;

use App\Models\Attachment;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Stack;
use App\Support\FractionalIndex;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CopyCard
{
	public function __construct(
		private CarryCardOver $carryCardOver,
		private RecordActivity $recordActivity,
	) {}

	/**
	 * Copies the card into a list (on any of the user's boards) at the given
	 * index. Title, description and dates always come along; $keep picks
	 * the rest: checklists, labels, members, attachments. Comments stay.
	 *
	 * @param  list<string>  $keep
	 */
	public function handle(Card $card, Stack $target, int $index, string $title, array $keep): Card
	{
		return DB::transaction(function () use ($card, $target, $index, $title, $keep): Card {
			$siblings = $target->cards()
				->active()
				->get(['id', 'position'])
				->map(fn (Card $sibling): string => $sibling->position)
				->all();

			$copy = new Card([
				'title' => $title,
				'description' => $card->description,
				'start_at' => $card->start_at,
				'due_at' => $card->due_at,
				'cover_color' => $card->cover_color,
				'position' => FractionalIndex::forIndex($siblings, $index),
			]);
			$copy->stack()->associate($target);
			$copy->save();

			if (in_array('labels', $keep, true)) {
				$copy->labels()->sync($card->labels()->pluck('labels.id'));
			}

			if (in_array('members', $keep, true)) {
				$copy->members()->sync($card->members()->pluck('users.id'));
			}

			if (in_array('checklists', $keep, true)) {
				$this->copyChecklists($card, $copy);
			}

			if (in_array('attachments', $keep, true)) {
				$this->copyAttachments($card, $copy, $target->project_id);
			}

			if ($target->project_id !== $card->stack->project_id) {
				$this->carryCardOver->handle($copy, $target->project);
			}

			$this->recordActivity->handle($copy, 'copied', ['from' => $card->title]);

			return $copy;
		});
	}

	private function copyChecklists(Card $card, Card $copy): void
	{
		$card->checklists()->with('items')->get()->each(function (Checklist $checklist) use ($copy): void {
			$new = $copy->checklists()->create(['title' => $checklist->title]);

			$checklist->items->each(fn (ChecklistItem $item) => $new->items()->create([
				'title' => $item->title,
				'completed_at' => $item->completed_at,
			]));
		});
	}

	private function copyAttachments(Card $card, Card $copy, int $projectId): void
	{
		$disk = Storage::disk(Attachment::DISK);
		$directory = Attachment::directoryFor($projectId, $copy->id);

		// Oldest first, so the copies keep the same order.
		foreach ($card->attachments()->reorder()->oldest('id')->get() as $attachment) {
			$extension = pathinfo($attachment->path, PATHINFO_EXTENSION);
			$path = $directory.'/'.Str::uuid().($extension !== '' ? ".{$extension}" : '');

			if ($disk->exists($attachment->path)) {
				$disk->copy($attachment->path, $path);
			}

			$new = $attachment->replicate(['card_id', 'path']);
			$new->path = $path;
			$new->card()->associate($copy);
			$new->save();

			if ($card->cover_attachment_id === $attachment->id) {
				$copy->update(['cover_attachment_id' => $new->id]);
			}
		}
	}
}
