<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class FinalizacionService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
    ) {
    }

    /**
     * RF-11: solo el responsable principal puede completar la tarea, y solo
     * si ningun guard registrado (RF-22 dependencias, RF-23 checklist, ver
     * config/tareas.php) la bloquea.
     */
    public function completar(Tarea $tarea, User $solicitante): Tarea
    {
        if (! $this->permisos->puedeCompletar($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                "Solo el responsable principal puede marcar la tarea como completada."
            );
        }

        if (! $tarea->estado->puedeTransicionarA(EstadoTarea::Completada)) {
            throw ValidationException::withMessages([
                "estado" => "La tarea no puede completarse desde su estado actual ({$tarea->estado->value}).",
            ]);
        }

        $motivosBloqueo = collect(config("tareas.guards_completar"))
            ->flatMap(fn (string $guardClass) => app($guardClass)->verificar($tarea))
            ->all();

        if (! empty($motivosBloqueo)) {
            throw ValidationException::withMessages([
                "bloqueos" => $motivosBloqueo,
            ]);
        }

        $tarea->update(["estado" => EstadoTarea::Completada]);

        $this->historial->registrar($tarea, TipoEvento::Completada, $solicitante);

        return $tarea->fresh();
    }
}
