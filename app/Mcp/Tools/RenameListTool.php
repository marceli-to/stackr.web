<?php

namespace App\Mcp\Tools;

use App\Actions\Stacks\RenameStack;
use App\Http\Requests\StackRequest;
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

#[Name('rename_list')]
#[Title('Rename list')]
#[Description('Give a list on a board a new name.')]
#[IsIdempotent]
class RenameListTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, RenameStack $renameStack): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'board' => ['required', 'string'],
			'list' => ['required'],
			...(new StackRequest)->rules(),
		]);

		$board = $lookup->board($user, $data['board']);
		$stack = $lookup->boardStack($user, $board, $data['list']);

		$renameStack->handle($stack, $data['name']);

		return Response::json(Shapes::list($stack->refresh()));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'board' => $schema->string()->description('The board key, or the board\'s exact name.')->required(),
			'list' => $schema->string()->description('The list\'s current name (any case) or id.')->required(),
			'name' => $schema->string()->description('The new name.')->required(),
		];
	}
}
