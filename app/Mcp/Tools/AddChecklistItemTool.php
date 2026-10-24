<?php

namespace App\Mcp\Tools;

use App\Actions\Checklists\CreateChecklistItem;
use App\Http\Requests\ChecklistItemRequest;
use App\Mcp\Boards;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('add_checklist_item')]
#[Title('Add checklist item')]
#[Description('Add an item to the end of an existing checklist. Checklist ids come from get_card.')]
class AddChecklistItemTool extends Tool
{
	public function handle(Request $request, Boards $boards, CreateChecklistItem $createChecklistItem): Response
	{
		$user = $boards->user($request);

		$data = $request->validate([
			'checklist_id' => ['required', 'integer'],
			...(new ChecklistItemRequest)->rules(),
		]);

		$checklist = $boards->checklist($user, (int) $data['checklist_id']);

		$item = $createChecklistItem->handle($checklist, $data['title']);

		return Response::json(['id' => $item->id, 'title' => $item->title, 'done' => false]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'checklist_id' => $schema->integer()->description('The checklist id.')->required(),
			'title' => $schema->string()->description('The item title.')->required(),
		];
	}
}
