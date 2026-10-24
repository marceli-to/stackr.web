<?php

namespace App\Mcp\Tools;

use App\Actions\Cards\AddLabel;
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

#[Name('add_label')]
#[Title('Add label')]
#[Description('Put one of the board\'s labels on a card.')]
#[IsIdempotent]
class AddLabelTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, AddLabel $addLabel): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'card_id' => ['required', 'integer'],
			'label' => ['required'],
		]);

		$card = $lookup->card($user, (int) $data['card_id']);

		$addLabel->handle($card, $lookup->label($card->stack->board, $data['label']));

		return Response::json(Shapes::card($card));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'card_id' => $schema->integer()->description('The card id.')->required(),
			'label' => $schema->string()->description('The label\'s name (any case) or id (see get_board).')->required(),
		];
	}
}
