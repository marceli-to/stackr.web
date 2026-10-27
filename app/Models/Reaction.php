<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $comment_id
 * @property int $user_id
 * @property string $emoji
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['emoji'])]
#[Table('comment_reactions')]
class Reaction extends Model
{
	/**
	 * The emojis people can react with, in the order they are offered.
	 */
	public const EMOJIS = ['👍', '❤️', '😂', '😄', '🎉', '👀', '🙏', '🔥', '🚀', '✅', '😮', '👎'];

	/**
	 * @return BelongsTo<Comment, $this>
	 */
	public function comment(): BelongsTo
	{
		return $this->belongsTo(Comment::class);
	}

	/**
	 * @return BelongsTo<User, $this>
	 */
	public function user(): BelongsTo
	{
		return $this->belongsTo(User::class);
	}
}
