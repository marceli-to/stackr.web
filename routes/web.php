<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\CardLabelController;
use App\Http\Controllers\CardMemberController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectBackgroundController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectInvitationController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\StackController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Guests get the landing page, signed-in users go straight to their projects.
Route::get('/', fn () => auth()->check() ? to_route('projects.index') : Inertia::render('site/Welcome'))->name('home');
Route::inertia('legal', 'site/Legal')->name('legal');

// Invitation links work signed out too: guests sign up or log in first and come back.
Route::get('invitations/{token}', [ProjectInvitationController::class, 'show'])->name('invitations.show');

Route::middleware(['auth', 'verified'])->group(function () {
	// Fortify sends users here after login; projects are the home screen.
	Route::redirect('dashboard', '/projects')->name('dashboard');

	Route::resource('projects', ProjectController::class)->except(['create', 'edit']);

	Route::post('notifications/read', [NotificationController::class, 'readAll'])->name('notifications.read-all');
	Route::post('notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');

	Route::get('projects/{project}/background', [ProjectBackgroundController::class, 'show'])->name('projects.background');
	Route::post('projects/{project}/background', [ProjectBackgroundController::class, 'store'])->name('projects.background.store');
	Route::delete('projects/{project}/background', [ProjectBackgroundController::class, 'destroy'])->name('projects.background.destroy');

	Route::post('projects/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
	Route::delete('projects/{project}/members/{user}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
	Route::delete('invitations/{invitation}', [ProjectInvitationController::class, 'destroy'])->name('invitations.destroy');

	Route::post('projects/{project}/stacks', [StackController::class, 'store'])->name('stacks.store');
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

	Route::post('projects/{project}/labels', [LabelController::class, 'store'])->name('labels.store');
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
