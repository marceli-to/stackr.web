<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Fortify sends users here after login; projects are the home screen.
    Route::redirect('dashboard', '/projects')->name('dashboard');

    Route::resource('projects', ProjectController::class)->except(['create', 'edit']);
});

require __DIR__.'/settings.php';
