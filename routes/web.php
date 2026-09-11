<?php

use App\Http\Controllers\ChecklistPersonalController;
use App\Http\Controllers\MisTareasController;
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

    Route::get('mis-tareas', [MisTareasController::class, 'index'])->name('mis-tareas.index');
    Route::post('tareas', [TareaController::class, 'store'])->name('tareas.store');
    Route::get('tareas/{tarea}', [TareaController::class, 'show'])->name('tareas.show');
    Route::patch('tareas/{tarea}/reasignar', [TareaController::class, 'reasignar'])->name('tareas.reasignar');
    Route::post('tareas/{tarea}/colaboradores', [TareaController::class, 'agregarColaborador'])->name('tareas.colaboradores.store');
    Route::patch('tareas/{tarea}/completar', [TareaController::class, 'completar'])->name('tareas.completar');
    Route::patch('tareas/{tarea}/retroceder', [TareaController::class, 'retroceder'])->name('tareas.retroceder');
    Route::patch('tareas/{tarea}/reportar-problema', [TareaController::class, 'reportarProblema'])->name('tareas.reportar-problema');
    Route::patch('tareas/{tarea}/no-participar', [TareaController::class, 'reportarNoParticipacion'])->name('tareas.no-participar');
    Route::patch('tareas/{tarea}/cancelar', [TareaController::class, 'cancelar'])->name('tareas.cancelar');
    Route::post('tareas/{tarea}/adjuntos', [TareaController::class, 'agregarAdjunto'])->name('tareas.adjuntos.store');
    Route::get('tareas/{tarea}/adjuntos/{adjunto}/descargar', [TareaController::class, 'descargarAdjunto'])->name('tareas.adjuntos.descargar');
    Route::post('tareas/{tarea}/checklist-personal', [ChecklistPersonalController::class, 'store'])->name('tareas.checklist-personal.store');
    Route::patch('tareas/{tarea}/checklist-personal/{item}', [ChecklistPersonalController::class, 'alternar'])->name('tareas.checklist-personal.alternar');
    Route::delete('tareas/{tarea}/checklist-personal/{item}', [ChecklistPersonalController::class, 'destroy'])->name('tareas.checklist-personal.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
