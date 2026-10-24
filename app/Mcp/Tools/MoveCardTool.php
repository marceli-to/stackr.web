<?php

namespace App\Mcp\Tools;

use App\Actions\Cards\MoveCard;
use App\Mcp\Lookup;
use App\Mcp\Shapes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('move_card')]
#[Title('Move card')]
#[Description('Move a card to another list, e.g. from "To do" to "Done". A list name is looked up on the card\'s own board; a list id may be on another of your boards.')]
class MoveCardTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, MoveCard $moveCard): Response
	{
		$user = $lookup->user($request);

		$data = $request->validate([
			'card_id' => ['required', 'integer'],
			'list' => ['required'],
			'position' => ['sometimes', Rule::in(['top', 'bottom'])],
		]);

		$card = $lookup->card($user, (int) $data['card_id']);
		$target = $lookup->stack($user, $card->stack->board, $data['list']);

		$index = ($data['position'] ?? 'bottom') === 'top'
			? 0
			: $target->cards()->active()->whereKeyNot($card->id)->count();

		$moveCard->handle($card, $target, $index);

		return Response::json(Shapes::card($card->refresh()));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'card_id' => $schema->integer()->description('The card id.')->required(),
			'list' => $schema->string()->description('The target list\'s name (any case) or id.')->required(),
			'position' => $schema->string()->enum(['top', 'bottom'])->description('Where in the list. Defaults to bottom.'),
		];
	}
}
