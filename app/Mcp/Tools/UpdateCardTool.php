<?php

namespace App\Mcp\Tools;

use App\Actions\Cards\UpdateCard;
use App\Mcp\Lookup;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update_card')]
#[Title('Update card')]
#[Description('Change a card. Send only the fields to change; null clears description and dates. Use completed to mark it done or reopen it.')]
#[IsIdempotent]
class UpdateCardTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, UpdateCard $updateCard): Response
	{
		$user = $lookup->user($request);

		$request->validate(['card_id' => ['required', 'integer']]);

		$card = $lookup->card($user, (int) $request->get('card_id'));

		// The same rules as CardUpdateRequest, without the cover (that's for people).
		$data = $request->validate([
			'title' => ['sometimes', 'required', 'string', 'max:500'],
			'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
			'start_at' => ['sometimes', 'nullable', 'date'],
			'due_at' => ['sometimes', 'nullable', 'date', Rule::when(filled($request->get('start_at')), 'after_or_equal:start_at')],
			'completed' => ['sometimes', 'boolean'],
		]);

		$updateCard->handle($card, $data);

		return Response::json(Shapes::card($card));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'card_id' => $schema->integer()->description('The card id.')->required(),
			'title' => $schema->string(),
			'description' => $schema->string()->nullable()->description('Markdown.'),
			'start_at' => $schema->string()->nullable()->description('ISO 8601 date or date-time.'),
			'due_at' => $schema->string()->nullable()->description('ISO 8601 date or date-time.'),
			'completed' => $schema->boolean()->description('true marks the card done, false reopens it.'),
		];
	}
}
