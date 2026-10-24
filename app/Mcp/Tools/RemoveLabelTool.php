<?php

namespace App\Mcp\Tools;

use App\Actions\Cards\RemoveLabel;
use App\Mcp\Boards;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Name('remove_label')]
#[Title('Remove label')]
#[Description('Take a label off a card.')]
#[IsIdempotent]
class RemoveLabelTool extends Tool
{
	public function handle(Request $request, Boards $boards, RemoveLabel $removeLabel): Response
	{
		$user = $boards->user($request);

		$data = $request->validate([
			'card_id' => ['required', 'integer'],
			'label' => ['required'],
		]);

		$card = $boards->card($user, (int) $data['card_id']);

		$removeLabel->handle($card, $boards->label($card->stack->project, $data['label']));

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
