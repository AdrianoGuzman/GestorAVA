<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CancelacionService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-25: solo el responsable principal puede cancelar, con motivo
     * obligatorio. Cierre definitivo, sin devolver la tarea a nadie (a
     * diferencia de RF-13); no hay soft delete, la tarea y su historial se
     * conservan integros.
     */
    public function cancelar(Tarea $tarea, User $solicitante, string $motivo): Tarea
    {
        if (! $this->permisos->puedeCancelar($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                "Solo el responsable principal puede cancelar la tarea."
            );
        }

        if (! $tarea->estado->puedeTransicionarA(EstadoTarea::Cancelada)) {
            throw ValidationException::withMessages([
                "estado" => "La tarea no puede cancelarse desde su estado actual ({$tarea->estado->value}).",
            ]);
        }

        $tarea->update([
            "estado" => EstadoTarea::Cancelada,
            "motivo_cancelacion" => $motivo,
            "fecha_cancelacion" => Carbon::now(),
        ]);

        $this->historial->registrar($tarea, TipoEvento::Cancelacion, $solicitante, [
            "motivo" => $motivo,
        ]);

        $tarea = $tarea->fresh();

        foreach ($tarea->colaboradores as $colaborador) {
            $this->notificaciones->notificarCancelacion($colaborador, $tarea, $motivo);
        }

        return $tarea;
    }
}
