<?php

namespace App\Support;

use App\Models\Attachment;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use App\Models\Comment;
use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Shapes cards for the board: the face shown in a list, and the full card
 * shown when it is opened.
 */
class CardData
{
    /**
     * What a card's face on the board needs. Expects labels, members, cover,
     * checklists.items and the attachments and comments counts loaded.
     *
     * @return array<string, mixed>
     */
    public static function face(Card $card): array
    {
        $items = $card->checklists->flatMap(fn (Checklist $checklist) => $checklist->items);

        return [
            'id' => $card->id,
            'title' => $card->title,
            'has_description' => filled($card->description),
            'start_at' => $card->start_at,
            'due_at' => $card->due_at,
            'completed_at' => $card->completed_at,
            'labels' => $card->labels->pluck('id'),
            'members' => $card->members->pluck('id'),
            'cover' => $card->cover !== null ? self::image($card->cover) : null,
            'attachments_count' => $card->attachments_count ?? 0,
            'comments_count' => $card->comments_count ?? 0,
            'checklist' => $items->isEmpty() ? null : [
                'done' => $items->whereNotNull('completed_at')->count(),
                'total' => $items->count(),
            ],
        ];
    }

    /**
     * Everything the card dialog shows.
     *
     * @return array<string, mixed>
     */
    public static function detail(Card $card, User $viewer): array
    {
        // The board loads only what card faces need (e.g. checklist items
        // without their titles), so the open card loads its own copies.
        $card->load(['checklists.items', 'attachments.user', 'comments.user']);
        $card->loadMissing(['stack', 'labels', 'members']);

        return [
            'id' => $card->id,
            'title' => $card->title,
            'description' => $card->description,
            'description_html' => self::markdown($card->description),
            'start_at' => $card->start_at,
            'due_at' => $card->due_at,
            'completed_at' => $card->completed_at,
            'created_at' => $card->created_at,
            'stack' => $card->stack->only('id', 'name'),
            'labels' => $card->labels->pluck('id'),
            'members' => $card->members->pluck('id'),
            'cover_attachment_id' => $card->cover_attachment_id,
            'checklists' => $card->checklists->map(fn (Checklist $checklist) => [
                'id' => $checklist->id,
                'title' => $checklist->title,
                'items' => $checklist->items->map(fn (ChecklistItem $item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'done' => $item->completed_at !== null,
                ]),
            ]),
            'attachments' => $card->attachments->map(fn (Attachment $attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'is_image' => $attachment->isImage(),
                'url' => route('attachments.show', $attachment),
                'download_url' => route('attachments.show', ['attachment' => $attachment, 'download' => 1]),
                'width' => $attachment->width,
                'height' => $attachment->height,
                'user' => $attachment->user?->name,
                'created_at' => $attachment->created_at,
            ]),
            'comments' => $card->comments->map(fn (Comment $comment) => [
                'id' => $comment->id,
                'body' => $comment->body,
                'body_html' => self::markdown($comment->body),
                'user' => $comment->user?->only('id', 'name'),
                'mine' => $comment->user_id === $viewer->id,
                'created_at' => $comment->created_at,
                'edited' => $comment->updated_at?->gt($comment->created_at->addSecond()) ?? false,
            ]),
        ];
    }

    /**
     * A project's labels for the board and the label picker.
     *
     * @return array<string, mixed>
     */
    public static function label(Label $label): array
    {
        return $label->only('id', 'name', 'color');
    }

    /**
     * @return array{url: string, width: int|null, height: int|null}
     */
    private static function image(Attachment $attachment): array
    {
        return [
            'url' => route('attachments.show', $attachment),
            'width' => $attachment->width,
            'height' => $attachment->height,
        ];
    }

    /**
     * Markdown to HTML, with raw HTML stripped and unsafe links dropped.
     */
    private static function markdown(?string $text): ?string
    {
        if (blank($text)) {
            return null;
        }

        return Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ]);
    }
}
