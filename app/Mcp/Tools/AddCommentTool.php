<?php

namespace App\Mcp\Tools;

use App\Actions\Comments\CreateComment;
use App\Http\Requests\CommentRequest;
use App\Mcp\Lookup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('add_comment')]
#[Title('Add comment')]
#[Description('Comment on a card, in Markdown. Mention a member with @Name to notify them.')]
class AddCommentTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, CreateComment $createComment): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'card_id' => ['required', 'integer'],
			...(new CommentRequest)->rules(),
		]);

		$card = $lookup->card($user, (int) $data['card_id']);

		$comment = $createComment->handle($card, $user, $data['body']);

		return Response::json(['id' => $comment->id, 'card_id' => $card->id]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'card_id' => $schema->integer()->description('The card id.')->required(),
			'body' => $schema->string()->description('The comment, in Markdown.')->required(),
		];
	}
}
