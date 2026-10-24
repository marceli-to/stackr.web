<?php

namespace App\Mcp\Tools;

use App\Actions\Cards\CreateCard;
use App\Actions\Cards\UpdateCard;
use App\Http\Requests\CardRequest;
use App\Mcp\Boards;
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
	public function handle(Request $request, Boards $boards, CreateCard $createCard, UpdateCard $updateCard): Response
	{
		$user = $boards->user($request);

		$data = $request->validate([
			'project' => ['required', 'string'],
			'list' => ['required'],
			...(new CardRequest)->rules(),
			'description' => ['nullable', 'string', 'max:20000'],
		]);

		$project = $boards->project($user, $data['project']);
		$stack = $boards->stack($user, $project, $data['list']);

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
			'project' => $schema->string()->description('The project key, or the project\'s exact name.')->required(),
			'list' => $schema->string()->description('The list\'s name (any case) or id.')->required(),
			'title' => $schema->string()->description('The card title.')->required(),
			'description' => $schema->string()->description('Optional description, in Markdown.'),
		];
	}
}
