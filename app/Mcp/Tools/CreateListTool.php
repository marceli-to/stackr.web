<?php

namespace App\Mcp\Tools;

use App\Actions\Stacks\CreateStack;
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

#[Name('create_list')]
#[Title('Create list')]
#[Description('Add a list to the end of a board. Returns the board\'s lists in order; use move_list to put it elsewhere.')]
class CreateListTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, CreateStack $createStack): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'board' => ['required', 'string'],
			...(new StackRequest)->rules(),
		]);

		$board = $lookup->board($user, $data['board']);
		$stack = $createStack->handle($board, $data['name']);

		return Response::json(['list' => Shapes::list($stack), 'lists' => Shapes::lists($board)]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'board' => $schema->string()->description('The board key, or the board\'s exact name.')->required(),
			'name' => $schema->string()->description('The list name.')->required(),
		];
	}
}
