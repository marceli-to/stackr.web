<?php

namespace App\Mcp\Tools;

use App\Actions\Labels\UpdateLabel;
use App\Enums\LabelColor;
use App\Mcp\Lookup;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('update_label')]
#[Title('Update label')]
#[Description('Rename or recolour one of a board\'s labels; every card with it changes too. Send only what changes; an empty name leaves just the colour.')]
#[IsIdempotent]
class UpdateLabelTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, UpdateLabel $updateLabel): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'board' => ['required', 'string'],
			'label' => ['required'],
			'name' => ['sometimes', 'nullable', 'string', 'max:60'],
			'color' => ['sometimes', Rule::enum(LabelColor::class)],
		]);

		$board = $lookup->board($user, $data['board']);
		$label = $lookup->label($board, $data['label']);

		$changes = Arr::only($data, ['name', 'color']);

		if (array_key_exists('name', $changes) && blank($changes['name'])) {
			$changes['name'] = null;
		}

		$updateLabel->handle($label, $changes);

		return Response::json(Shapes::label($label->refresh()));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'board' => $schema->string()->description('The board key, or the board\'s exact name.')->required(),
			'label' => $schema->string()->description('The label\'s current name (any case) or id (see get_board).')->required(),
			'name' => $schema->string()->description('The new name, up to 60 characters.'),
			'color' => $schema->string()->enum(array_column(LabelColor::cases(), 'value'))->description('The new colour.'),
		];
	}
}
