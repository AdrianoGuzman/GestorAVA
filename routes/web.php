<?php

use App\Http\Controllers\ChecklistController;
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
    Route::patch('tareas/{tarea}/reasignar', [TareaController::class, 'reasignar'])->name('tareas.reasignar');

    Route::post('tareas/{tarea}/checklist', [ChecklistController::class, 'store'])->name('checklist.store');
    Route::patch('checklist/{checklistItem}', [ChecklistController::class, 'update'])->name('checklist.update');
    Route::patch('checklist/{checklistItem}/marcar', [ChecklistController::class, 'marcar'])->name('checklist.marcar');
    Route::patch('checklist/{checklistItem}/desmarcar', [ChecklistController::class, 'desmarcar'])->name('checklist.desmarcar');
    Route::delete('checklist/{checklistItem}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
