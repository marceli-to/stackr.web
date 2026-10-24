<?php

namespace App\Mcp\Tools;

use App\Mcp\Lookup;
use App\Mcp\Shapes;
use App\Models\Board;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_boards')]
#[Title('List boards')]
#[Description('List the boards you are a member of. Use a board\'s key with get_board.')]
#[IsReadOnly]
class ListBoardsTool extends Tool
{
	public function handle(Request $request, Lookup $lookup): Response
	{
		$boards = $lookup->user($request)->boards()->orderBy('name')->get();

		return Response::json($boards->map(fn (Board $board): array => Shapes::summary($board))->all());
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [];
	}
}
