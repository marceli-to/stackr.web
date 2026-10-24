<?php

namespace App\Mcp\Tools;

use App\Mcp\Lookup;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('get_card')]
#[Title('Get card')]
#[Description('Get everything on a card: description, list, dates, labels, members, checklists with item ids, and the latest 50 comments (newest first).')]
#[IsReadOnly]
class GetCardTool extends Tool
{
	public function handle(Request $request, Lookup $lookup): Response
	{
		$user = $lookup->user($request);

		$request->validate(['card_id' => ['required', 'integer']]);

		$card = $lookup->card($user, (int) $request->get('card_id'), 'view');

		return Response::json(Shapes::card($card));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'card_id' => $schema->integer()->description('The card id from get_board.')->required(),
		];
	}
}
