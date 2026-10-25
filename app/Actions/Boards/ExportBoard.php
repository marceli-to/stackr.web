<?php

namespace App\Actions\Boards;

use App\Models\Attachment;
use App\Models\Board;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Stack;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class ExportBoard
{
	/** Bumped when the shape of board.json changes. */
	public const VERSION = 1;

	/**
	 * Packs the board and its active lists and cards into a ZIP: board.json
	 * with everything on them, and the cards' files under attachments/.
	 * Archived lists and cards and the activity log stay out. Returns the
	 * path of a temporary file the caller sends and deletes.
	 */
	public function handle(Board $board): string
	{
		$board->load([
			'labels',
			'stacks' => fn (HasMany $query) => $query->active(),
			'stacks.cards' => fn (HasMany $query) => $query->active(),
			'stacks.cards.labels',
			'stacks.cards.members',
			'stacks.cards.checklists.items',
			'stacks.cards.comments' => fn (HasMany $query) => $query->reorder()->oldest('id'),
			'stacks.cards.comments.user',
			'stacks.cards.attachments' => fn (HasMany $query) => $query->reorder()->oldest('id'),
			'stacks.cards.attachments.user',
		]);

		$path = tempnam(sys_get_temp_dir(), 'stackr-export-');
		$zip = new ZipArchive;

		if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
			throw new RuntimeException('Could not create the export file.');
		}

		$disk = Storage::disk(Attachment::DISK);
		$files = [];

		foreach ($board->stacks as $stack) {
			foreach ($stack->cards as $card) {
				foreach ($card->attachments as $attachment) {
					if ($disk->exists($attachment->path)) {
						$files[$attachment->id] = "attachments/{$card->id}/{$attachment->id}-".$this->safeName($attachment->name);
						$zip->addFile($disk->path($attachment->path), $files[$attachment->id]);
					}
				}
			}
		}

		$zip->addFromString('board.json', (string) json_encode(
			$this->board($board, $files),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
		));
		$zip->close();

		return $path;
	}

	/**
	 * @param  array<int, string>  $files  attachment id => path in the ZIP
	 * @return array<string, mixed>
	 */
	private function board(Board $board, array $files): array
	{
		return [
			'version' => self::VERSION,
			'exported_at' => now()->toIso8601String(),
			'board' => [
				'name' => $board->name,
				'description' => $board->description,
				'url' => $board->url,
				'color' => $board->color->value,
				'created_at' => $this->date($board->created_at),
			],
			'members' => $board->membershipsInOrder()->map(fn ($membership) => [
				'name' => $membership->user->name,
				'email' => $membership->user->email,
				'role' => $membership->role->value,
			])->all(),
			'labels' => $board->labels->map(fn (Label $label) => $this->label($label))->all(),
			'lists' => $board->stacks->map(fn (Stack $stack) => [
				'name' => $stack->name,
				'wip_limit' => $stack->wip_limit,
				'cards' => $stack->cards->map(fn (Card $card) => $this->card($card, $files))->all(),
			])->all(),
		];
	}

	/**
	 * @param  array<int, string>  $files
	 * @return array<string, mixed>
	 */
	private function card(Card $card, array $files): array
	{
		return [
			'id' => $card->id,
			'title' => $card->title,
			'description' => $card->description,
			'start_at' => $this->date($card->start_at),
			'due_at' => $this->date($card->due_at),
			'completed_at' => $this->date($card->completed_at),
			'is_template' => $card->is_template,
			'cover_color' => $card->cover_color?->value,
			'cover_file' => $card->cover_attachment_id !== null ? ($files[$card->cover_attachment_id] ?? null) : null,
			'labels' => $card->labels->map(fn (Label $label) => $this->label($label))->all(),
			'members' => $card->members->map(fn (User $user) => $user->name)->all(),
			'checklists' => $card->checklists->map(fn (Checklist $checklist) => [
				'title' => $checklist->title,
				'items' => $checklist->items->map(fn (ChecklistItem $item) => [
					'title' => $item->title,
					'completed_at' => $this->date($item->completed_at),
				])->all(),
			])->all(),
			'comments' => $card->comments->map(fn (Comment $comment) => [
				'author' => $comment->user?->name,
				'via_agent' => $comment->via_agent,
				'body' => $comment->body,
				'created_at' => $this->date($comment->created_at),
			])->all(),
			'attachments' => $card->attachments->map(fn (Attachment $attachment) => [
				'name' => $attachment->name,
				'file' => $files[$attachment->id] ?? null,
				'mime_type' => $attachment->mime_type,
				'size' => $attachment->size,
				'uploaded_by' => $attachment->user?->name,
				'created_at' => $this->date($attachment->created_at),
			])->all(),
			'created_at' => $this->date($card->created_at),
			'updated_at' => $this->date($card->updated_at),
		];
	}

	/**
	 * @return array<string, string|null>
	 */
	private function label(Label $label): array
	{
		return ['name' => $label->name, 'color' => $label->color->value];
	}

	private function date(?CarbonInterface $date): ?string
	{
		return $date?->toIso8601String();
	}

	/**
	 * A file name that can't climb out of its folder in the ZIP.
	 */
	private function safeName(string $name): string
	{
		$name = trim(str_replace(['/', '\\', "\0"], '-', $name), '. ');

		return $name !== '' ? $name : 'file';
	}
}
