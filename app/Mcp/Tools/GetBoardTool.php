<?php

namespace App\Mcp\Tools;

use App\Mcp\Boards;
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
	public function handle(Request $request, Boards $boards): Response
	{
		$user = $boards->user($request);

		$request->validate(['project' => ['required', 'string']]);

		$project = $boards->project($user, $request->get('project'), 'view');

		return Response::json(Shapes::board($project));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'project' => $schema->string()->description('The project key from list_projects, or the project\'s exact name.')->required(),
		];
	}
}
