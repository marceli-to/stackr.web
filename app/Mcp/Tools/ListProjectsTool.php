<?php

namespace App\Mcp\Tools;

use App\Mcp\Boards;
use App\Mcp\Shapes;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('list_projects')]
#[Title('List projects')]
#[Description('List the projects (boards) you are a member of. Use a project\'s key with get_board.')]
#[IsReadOnly]
class ListProjectsTool extends Tool
{
	public function handle(Request $request, Boards $boards): Response
	{
		$projects = $boards->user($request)->projects()->orderBy('name')->get();

		return Response::json($projects->map(fn (Project $project): array => Shapes::project($project))->all());
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [];
	}
}
