<?php

namespace App\Mcp\Tools;

use App\Actions\Stacks\MoveStack;
use App\Mcp\Lookup;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('move_list')]
#[Title('Move list')]
#[Description('Put a list at another place on its board, counted from the left starting at 1. Returns the board\'s lists in their new order.')]
#[IsIdempotent]
class MoveListTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, MoveStack $moveStack): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'board' => ['required', 'string'],
			'list' => ['required'],
			'position' => ['required', 'integer', 'min:1'],
		]);

		$board = $lookup->board($user, $data['board']);
		$stack = $lookup->boardStack($user, $board, $data['list']);

		$moveStack->handle($stack, (int) $data['position'] - 1);

		return Response::json(['lists' => Shapes::lists($board)]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'board' => $schema->string()->description('The board key, or the board\'s exact name.')->required(),
			'list' => $schema->string()->description('The list\'s name (any case) or id.')->required(),
			'position' => $schema->integer()->description('1 for the leftmost place; a number past the end puts it last.')->required(),
		];
	}
}
