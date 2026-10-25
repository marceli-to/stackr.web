<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\BoardBackgroundController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\BoardExportController;
use App\Http\Controllers\BoardInvitationController;
use App\Http\Controllers\BoardMemberController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardLabelController;
use App\Http\Controllers\CardMemberController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\InviteLinkController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StackController;
use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Guests get the landing page, signed-in users go straight to their boards.
Route::get('/', fn () => auth()->check() ? to_route('boards.index') : Inertia::render('site/Welcome'))->name('home');
Route::inertia('legal', 'site/Legal')->name('legal');

// Invitation links work signed out too: guests sign up or log in first and come back.
Route::get('invitations/{token}', [BoardInvitationController::class, 'show'])->name('invitations.show');
Route::get('join/{token}', [InviteLinkController::class, 'show'])->name('invite-link.show');

Route::middleware(['auth', 'verified'])->group(function () {
	// Fortify sends users here after login; boards are the home screen.
	Route::redirect('dashboard', '/boards')->name('dashboard');

	Route::resource('boards', BoardController::class)->except(['create', 'edit']);

	Route::post('notifications/read', [NotificationController::class, 'readAll'])->name('notifications.read-all');
	Route::post('notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');

	Route::get('boards/{board}/background', [BoardBackgroundController::class, 'show'])->name('boards.background');
	Route::post('boards/{board}/background', [BoardBackgroundController::class, 'store'])->name('boards.background.store');
	Route::delete('boards/{board}/background', [BoardBackgroundController::class, 'destroy'])->name('boards.background.destroy');

	Route::get('boards/{board}/export', [BoardExportController::class, 'show'])->name('boards.export');

	Route::post('boards/{board}/watch', [WatchController::class, 'board'])->name('boards.watch');
	Route::delete('boards/{board}/watch', [WatchController::class, 'board'])->name('boards.unwatch');
	Route::post('cards/{card}/watch', [WatchController::class, 'card'])->name('cards.watch');
	Route::delete('cards/{card}/watch', [WatchController::class, 'card'])->name('cards.unwatch');

	Route::post('boards/{board}/members', [BoardMemberController::class, 'store'])->name('boards.members.store');
	Route::delete('boards/{board}/members/{user}', [BoardMemberController::class, 'destroy'])->name('boards.members.destroy');
	Route::post('boards/{board}/invite-link', [InviteLinkController::class, 'store'])->name('invite-link.store');
	Route::delete('boards/{board}/invite-link', [InviteLinkController::class, 'destroy'])->name('invite-link.destroy');
	Route::delete('invitations/{invitation}', [BoardInvitationController::class, 'destroy'])->name('invitations.destroy');

	Route::post('boards/{board}/stacks', [StackController::class, 'store'])->name('stacks.store');
	Route::patch('stacks/{stack}', [StackController::class, 'update'])->name('stacks.update');
	Route::patch('stacks/{stack}/limit', [StackController::class, 'limit'])->name('stacks.limit');
	Route::patch('stacks/{stack}/move', [StackController::class, 'move'])->name('stacks.move');
	Route::patch('stacks/{stack}/archive', [StackController::class, 'archive'])->name('stacks.archive');
	Route::patch('stacks/{stack}/restore', [StackController::class, 'restore'])->name('stacks.restore');
	Route::delete('stacks/{stack}', [StackController::class, 'destroy'])->name('stacks.destroy');

	Route::post('stacks/{stack}/cards', [CardController::class, 'store'])->name('cards.store');
	Route::post('stacks/{stack}/cards/from/{card}', [CardController::class, 'fromTemplate'])->name('cards.from-template');
	Route::patch('cards/{card}/move', [CardController::class, 'move'])->name('cards.move');
	Route::patch('cards/{card}', [CardController::class, 'update'])->name('cards.update');
	Route::post('cards/{card}/copy', [CardController::class, 'copy'])->name('cards.copy');
	Route::patch('cards/{card}/archive', [CardController::class, 'archive'])->name('cards.archive');
	Route::patch('cards/{card}/restore', [CardController::class, 'restore'])->name('cards.restore');
	Route::delete('cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');

	Route::post('boards/{board}/labels', [LabelController::class, 'store'])->name('labels.store');
	Route::patch('labels/{label}', [LabelController::class, 'update'])->name('labels.update');
	Route::delete('labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');
	Route::post('cards/{card}/labels/{label}', [CardLabelController::class, 'store'])->name('cards.labels.store');
	Route::delete('cards/{card}/labels/{label}', [CardLabelController::class, 'destroy'])->name('cards.labels.destroy');

	Route::post('cards/{card}/members/{user}', [CardMemberController::class, 'store'])->name('cards.members.store');
	Route::delete('cards/{card}/members/{user}', [CardMemberController::class, 'destroy'])->name('cards.members.destroy');

	Route::post('cards/{card}/checklists', [ChecklistController::class, 'store'])->name('checklists.store');
	Route::patch('checklists/{checklist}', [ChecklistController::class, 'update'])->name('checklists.update');
	Route::delete('checklists/{checklist}', [ChecklistController::class, 'destroy'])->name('checklists.destroy');
	Route::post('checklists/{checklist}/items', [ChecklistItemController::class, 'store'])->name('checklist-items.store');
	Route::patch('checklist-items/{item}', [ChecklistItemController::class, 'update'])->name('checklist-items.update');
	Route::delete('checklist-items/{item}', [ChecklistItemController::class, 'destroy'])->name('checklist-items.destroy');

	Route::post('cards/{card}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
	Route::get('attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
	Route::get('attachments/{attachment}/thumbnail', [AttachmentController::class, 'thumbnail'])->name('attachments.thumbnail');
	Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');

	Route::post('cards/{card}/comments', [CommentController::class, 'store'])->name('comments.store');
	Route::patch('comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
	Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

require __DIR__.'/settings.php';
