<?php

namespace App\Mcp\Tools;

use App\Actions\Cards\AssignMember;
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

#[Name('assign_member')]
#[Title('Assign member')]
#[Description('Assign a board member to a card. They get notified, unless it\'s you.')]
#[IsIdempotent]
class AssignMemberTool extends Tool
{
	public function handle(Request $request, Boards $boards, AssignMember $assignMember): Response
	{
		$user = $boards->user($request);

		$data = $request->validate([
			'card_id' => ['required', 'integer'],
			'member' => ['required'],
		]);

		$card = $boards->card($user, (int) $data['card_id']);

		$assignMember->handle($card, $boards->member($card->stack->project, $data['member']));

		return Response::json(Shapes::card($card));
	}

	/**
	 * @return array<string, mixed>
	 */
	public function schema(JsonSchema $schema): array
	{
		return [
			'card_id' => $schema->integer()->description('The card id.')->required(),
			'member' => $schema->string()->description('The board member\'s name, email or id (see get_board).')->required(),
		];
	}
}
