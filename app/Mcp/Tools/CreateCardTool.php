<?php

namespace App\Mcp\Tools;

use App\Actions\Cards\CreateCard;
use App\Actions\Cards\UpdateCard;
use App\Http\Requests\CardRequest;
use App\Mcp\Lookup;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('create_card')]
#[Title('Create card')]
#[Description('Add a card to the bottom of a list, optionally with a description.')]
class CreateCardTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, CreateCard $createCard, UpdateCard $updateCard): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'board' => ['required', 'string'],
			'list' => ['required'],
			...(new CardRequest)->rules(),
			'description' => ['nullable', 'string', 'max:20000'],
		]);

		$board = $lookup->board($user, $data['board']);
		$stack = $lookup->stack($user, $board, $data['list']);

		$card = $createCard->handle($stack, $data['title']);

		if (filled($data['description'] ?? null)) {
			$updateCard->handle($card, ['description' => $data['description']]);
		}

		return Response::json(Shapes::card($card));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'board' => $schema->string()->description('The board key, or the board\'s exact name.')->required(),
			'list' => $schema->string()->description('The list\'s name (any case) or id.')->required(),
			'title' => $schema->string()->description('The card title.')->required(),
			'description' => $schema->string()->description('Optional description, in Markdown.'),
		];
	}
}
