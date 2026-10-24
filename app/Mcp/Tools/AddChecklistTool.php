<?php

namespace App\Mcp\Tools;

use App\Actions\Checklists\CreateChecklist;
use App\Actions\Checklists\CreateChecklistItem;
use App\Http\Requests\ChecklistRequest;
use App\Mcp\Boards;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('add_checklist')]
#[Title('Add checklist')]
#[Description('Add a checklist to a card, optionally with its items, e.g. to break the work into steps.')]
class AddChecklistTool extends Tool
{
	public function handle(Request $request, Boards $boards, CreateChecklist $createChecklist, CreateChecklistItem $createChecklistItem): Response
	{
		$user = $boards->user($request);

		$data = $request->validate([
			'card_id' => ['required', 'integer'],
			...(new ChecklistRequest)->rules(),
			'items' => ['sometimes', 'array', 'max:100'],
			'items.*' => ['required', 'string', 'max:500'],
		]);

		$card = $boards->card($user, (int) $data['card_id']);

		$checklist = $createChecklist->handle($card, $data['title']);

		foreach ($data['items'] ?? [] as $title) {
			$createChecklistItem->handle($checklist, $title);
		}

		return Response::json(Shapes::card($card));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'card_id' => $schema->integer()->description('The card id.')->required(),
			'title' => $schema->string()->description('The checklist title, e.g. "Steps".')->required(),
			'items' => $schema->array()->items($schema->string())->description('Item titles, in order.'),
		];
	}
}
