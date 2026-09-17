<?php

use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\ChecklistPersonalController;
use App\Http\Controllers\DependenciaController;
use App\Http\Controllers\ExportarProyectoController;
use App\Http\Controllers\ExportarTareaController;
use App\Http\Controllers\MisTareasController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\SeccionController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('mis-tareas.index') : redirect()->route('login');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('mis-tareas', [MisTareasController::class, 'index'])->name('mis-tareas.index');

    Route::get('mis-proyectos', [ProyectoController::class, 'index'])->name('mis-proyectos.index');
    Route::get('proyectos/{proyecto}', [ProyectoController::class, 'show'])->name('proyectos.show');
    Route::post('proyectos', [ProyectoController::class, 'store'])->name('proyectos.store');
    Route::patch('proyectos/{proyecto}', [ProyectoController::class, 'update'])->name('proyectos.update');
    Route::patch('proyectos/{proyecto}/aplazar-entrega', [ProyectoController::class, 'aplazarEntrega'])->name('proyectos.aplazar-entrega');
    Route::post('proyectos/{proyecto}/secciones', [SeccionController::class, 'store'])->name('secciones.store');
    Route::patch('secciones/{seccion}', [SeccionController::class, 'update'])->name('secciones.update');
    Route::get('proyectos/{proyecto}/exportar-pdf', [ExportarProyectoController::class, 'pdf'])->name('proyectos.exportar-pdf');
    Route::get('proyectos/{proyecto}/exportar-excel', [ExportarProyectoController::class, 'excel'])->name('proyectos.exportar-excel');

    Route::get('notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('notificaciones/leer-todas', [NotificacionController::class, 'marcarTodasLeidas'])->name('notificaciones.leer-todas');
    Route::post('notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarLeida'])->name('notificaciones.leer');

    Route::get('administracion/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::post('administracion/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::patch('administracion/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios.update');
    Route::delete('administracion/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios.destroy');

    Route::post('tareas', [TareaController::class, 'store'])->name('tareas.store');
    Route::get('tareas/{tarea}', [TareaController::class, 'show'])->name('tareas.show');
    Route::patch('tareas/{tarea}', [TareaController::class, 'actualizar'])->name('tareas.actualizar');
    Route::patch('tareas/{tarea}/reasignar', [TareaController::class, 'reasignar'])->name('tareas.reasignar');
    Route::post('tareas/{tarea}/colaboradores', [TareaController::class, 'agregarColaborador'])->name('tareas.colaboradores.store');
    Route::patch('tareas/{tarea}/completar', [TareaController::class, 'completar'])->name('tareas.completar');
    Route::patch('tareas/{tarea}/retroceder', [TareaController::class, 'retroceder'])->name('tareas.retroceder');
    Route::patch('tareas/{tarea}/reportar-problema', [TareaController::class, 'reportarProblema'])->name('tareas.reportar-problema');
    Route::patch('tareas/{tarea}/no-participar', [TareaController::class, 'reportarNoParticipacion'])->name('tareas.no-participar');
    Route::patch('tareas/{tarea}/cancelar', [TareaController::class, 'cancelar'])->name('tareas.cancelar');
    Route::post('tareas/{tarea}/adjuntos', [TareaController::class, 'agregarAdjunto'])->name('tareas.adjuntos.store');
    Route::post('tareas/{tarea}/hijas', [DependenciaController::class, 'store'])->name('tareas.hijas.store');
    Route::get('tareas/{tarea}/adjuntos/{adjunto}/descargar', [TareaController::class, 'descargarAdjunto'])->name('tareas.adjuntos.descargar');
    Route::get('tareas/{tarea}/exportar-pdf', [ExportarTareaController::class, 'pdf'])->name('tareas.exportar-pdf');
    Route::get('tareas/{tarea}/exportar-excel', [ExportarTareaController::class, 'excel'])->name('tareas.exportar-excel');
    Route::post('tareas/{tarea}/checklist', [ChecklistController::class, 'store'])->name('checklist.store');
    Route::patch('checklist/{checklistItem}', [ChecklistController::class, 'update'])->name('checklist.update');
    Route::patch('checklist/{checklistItem}/marcar', [ChecklistController::class, 'marcar'])->name('checklist.marcar');
    Route::patch('checklist/{checklistItem}/desmarcar', [ChecklistController::class, 'desmarcar'])->name('checklist.desmarcar');
    Route::delete('checklist/{checklistItem}', [ChecklistController::class, 'destroy'])->name('checklist.destroy');
    Route::post('tareas/{tarea}/checklist-personal', [ChecklistPersonalController::class, 'store'])->name('tareas.checklist-personal.store');
    Route::patch('tareas/{tarea}/checklist-personal/{item}', [ChecklistPersonalController::class, 'alternar'])->name('tareas.checklist-personal.alternar');
    Route::delete('tareas/{tarea}/checklist-personal/{item}', [ChecklistPersonalController::class, 'destroy'])->name('tareas.checklist-personal.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';