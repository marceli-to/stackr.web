<?php

namespace App\Mcp\Tools;

use App\Actions\Checklists\UpdateChecklistItem;
use App\Mcp\Boards;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('tick_checklist_item')]
#[Title('Tick checklist item')]
#[Description('Tick a checklist item off, or untick it with done: false. Item ids come from get_card.')]
#[IsIdempotent]
class TickChecklistItemTool extends Tool
{
	public function handle(Request $request, Boards $boards, UpdateChecklistItem $updateChecklistItem): Response
	{
		$user = $boards->user($request);

		$data = $request->validate([
			'item_id' => ['required', 'integer'],
			'done' => ['sometimes', 'boolean'],
		]);

		$item = $boards->checklistItem($user, (int) $data['item_id']);

		$updateChecklistItem->handle($item, ['done' => $data['done'] ?? true]);

		return Response::json(['id' => $item->id, 'title' => $item->title, 'done' => $item->completed_at !== null]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'item_id' => $schema->integer()->description('The checklist item id.')->required(),
			'done' => $schema->boolean()->description('Defaults to true.'),
		];
	}
}
