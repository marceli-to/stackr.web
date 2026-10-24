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

#[Name('get_board')]
#[Title('Get board')]
#[Description('Get a board: its lists in order with their cards (id, title, due date, labels, members, checklist progress), plus the labels and members available on the board. Archived cards and lists are left out.')]
#[IsReadOnly]
class GetBoardTool extends Tool
{
	public function handle(Request $request, Lookup $lookup): Response
	{
		$user = $lookup->user($request);

		$request->validate(['board' => ['required', 'string']]);

		$board = $lookup->board($user, $request->get('board'), 'view');

		return Response::json(Shapes::board($board));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'board' => $schema->string()->description('The board key from list_boards, or the board\'s exact name.')->required(),
		];
	}
}
