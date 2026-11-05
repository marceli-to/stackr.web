<?php

namespace App\Mcp\Tools;

use App\Actions\Labels\CreateLabel;
use App\Enums\LabelColor;
use App\Http\Requests\LabelRequest;
use App\Mcp\Lookup;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('create_label')]
#[Title('Create label')]
#[Description('Add a label to a board, so add_label can put it on cards.')]
class CreateLabelTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, CreateLabel $createLabel): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'board' => ['required', 'string'],
			...(new LabelRequest)->rules(),
		]);

		$board = $lookup->board($user, $data['board']);
		$label = $createLabel->handle($board, [
			'name' => filled($data['name'] ?? null) ? $data['name'] : null,
			'color' => $data['color'],
		]);

		return Response::json(Shapes::label($label));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'board' => $schema->string()->description('The board key, or the board\'s exact name.')->required(),
			'name' => $schema->string()->description('The label name, up to 60 characters. Optional: a label can be just a colour.'),
			'color' => $schema->string()->enum(array_column(LabelColor::cases(), 'value'))->description('The label colour.')->required(),
		];
	}
}
