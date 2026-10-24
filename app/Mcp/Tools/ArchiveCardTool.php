<?php

namespace App\Mcp\Tools;

use App\Actions\Cards\ArchiveCard;
use App\Mcp\Lookup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Title;
use Laravel\Mcp\Server\Tool;

#[Name('archive_card')]
#[Title('Archive card')]
#[Description('Archive a card: it leaves the board but can be restored from the board\'s archive in the app.')]
class ArchiveCardTool extends Tool
{
	public function handle(Request $request, Lookup $lookup, ArchiveCard $archiveCard): Response
	{
		$user = $lookup->user($request);

		$request->validate(['card_id' => ['required', 'integer']]);

		$card = $lookup->card($user, (int) $request->get('card_id'));

		$archiveCard->handle($card);

		return Response::json(['id' => $card->id, 'archived' => true]);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'card_id' => $schema->integer()->description('The card id.')->required(),
		];
	}
}
