<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddChecklistItemTool;
use App\Mcp\Tools\AddChecklistTool;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\AddLabelTool;
use App\Mcp\Tools\ArchiveCardTool;
use App\Mcp\Tools\AssignMemberTool;
use App\Mcp\Tools\CreateCardTool;
use App\Mcp\Tools\CreateLabelTool;
use App\Mcp\Tools\CreateListTool;
use App\Mcp\Tools\GetBoardTool;
use App\Mcp\Tools\GetCardTool;
use App\Mcp\Tools\ListBoardsTool;
use App\Mcp\Tools\MoveCardTool;
use App\Mcp\Tools\MoveListTool;
use App\Mcp\Tools\RemoveLabelTool;
use App\Mcp\Tools\RenameListTool;
use App\Mcp\Tools\TickChecklistItemTool;
use App\Mcp\Tools\UnassignMemberTool;
use App\Mcp\Tools\UpdateCardTool;
use App\Mcp\Tools\UpdateLabelTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Stackr')]
#[Version('1.0.0')]
#[Instructions(<<<'MD'
	Stackr is a kanban app: boards hold lists, lists hold cards. You act as the user who
	made the token, on the boards they are a member of; everything you do shows in the card's
	history marked "via agent".

	Start with list_boards, then get_board to see the lists and card ids, and get_card for a
	card's description, checklists and comments. A typical loop: pick a card from "To do", move it
	to the list for work in progress, do the work, tick its checklist items, comment on what you
	did, then move it to "Done" (or mark it completed with update_card). Lists can be named by
	name or id; if a name is ambiguous you'll be asked for the id.

	You can also set up a board: create_list, rename_list and move_list for its lists, and
	create_label and update_label for its labels. Nothing can be deleted through these tools.
	MD)]
class StackrServer extends Server
{
	/**
	 * All tools on one page: laravel/mcp pages lists 15 at a time, and not
	 * every client asks for the next page.
	 */
	public int $defaultPaginationLength = 50;

	protected array $tools = [
		ListBoardsTool::class,
		GetBoardTool::class,
		GetCardTool::class,
		CreateCardTool::class,
		UpdateCardTool::class,
		MoveCardTool::class,
		AddCommentTool::class,
		AddChecklistTool::class,
		AddChecklistItemTool::class,
		TickChecklistItemTool::class,
		AssignMemberTool::class,
		UnassignMemberTool::class,
		AddLabelTool::class,
		RemoveLabelTool::class,
		ArchiveCardTool::class,
		CreateListTool::class,
		RenameListTool::class,
		MoveListTool::class,
		CreateLabelTool::class,
		UpdateLabelTool::class,
	];
}
