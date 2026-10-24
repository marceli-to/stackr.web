<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StackController;
use Illuminate\Support\Facades\Route;

// No landing page: guests go to the login, signed-in users to their projects.
Route::get('/', fn () => to_route(auth()->check() ? 'projects.index' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Fortify sends users here after login; projects are the home screen.
    Route::redirect('dashboard', '/projects')->name('dashboard');

    Route::resource('projects', ProjectController::class)->except(['create', 'edit']);

    Route::post('projects/{project}/stacks', [StackController::class, 'store'])->name('stacks.store');
    Route::patch('stacks/{stack}', [StackController::class, 'update'])->name('stacks.update');
    Route::patch('stacks/{stack}/move', [StackController::class, 'move'])->name('stacks.move');
    Route::delete('stacks/{stack}', [StackController::class, 'destroy'])->name('stacks.destroy');

    Route::post('stacks/{stack}/cards', [CardController::class, 'store'])->name('cards.store');
    Route::patch('cards/{card}/move', [CardController::class, 'move'])->name('cards.move');
});

require __DIR__.'/settings.php';
