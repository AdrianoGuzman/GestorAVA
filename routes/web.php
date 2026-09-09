<?php

use App\Http\Controllers\TareaController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::post('tareas', [TareaController::class, 'store'])->name('tareas.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
