<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RechazoService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-13: el responsable o un colaborador rechaza la tarea con motivo
     * obligatorio. La tarea pasa a Rechazada y queda disponible para que
     * quien la delegó por última vez la corrija y reasigne via RF-05 (D3);
     * no se reasigna automaticamente.
     */
    public function rechazar(Tarea $tarea, User $solicitante, string $motivo): Tarea
    {
        if (! $this->permisos->puedeRechazar($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                "Solo el responsable o un colaborador de la tarea puede rechazarla."
            );
        }

        if (! $tarea->estado->puedeTransicionarA(EstadoTarea::Rechazada)) {
            throw ValidationException::withMessages([
                "estado" => "La tarea no puede rechazarse desde su estado actual ({$tarea->estado->value}).",
            ]);
        }

        $delegador = $this->obtenerDelegador($tarea);

        $tarea->update(["estado" => EstadoTarea::Rechazada]);

        $this->historial->registrar($tarea, TipoEvento::Rechazo, $solicitante, [
            "motivo" => $motivo,
        ]);

        $tarea = $tarea->fresh();

        if ($delegador && $delegador->id !== $solicitante->id) {
            $this->notificaciones->notificarRechazo($delegador, $tarea, $solicitante, $motivo);
        }

        return $tarea;
    }

    /**
     * RF-13 D3 (con la ambiguedad de "seccion 7, punto 6" ya resuelta en la
     * spec): quien hizo la reasignacion inmediatamente anterior al rechazo,
     * no el responsable original de toda la historia. Si la tarea nunca fue
     * reasignada, quien asigno el responsable actual fue el creador (RF-04
     * D2 trata esa asignacion como equivalente a una reasignacion). Si el
     * propio creador es el responsable (nunca hubo delegacion real), no hay
     * a quien devolver la tarea.
     */
    private function obtenerDelegador(Tarea $tarea): ?User
    {
        $ultimaReasignacion = $tarea->historial()
            ->whereIn("tipo_evento", [TipoEvento::Reasignacion, TipoEvento::ReasignacionExcepcional])
            ->latest("id")
            ->first();

        if ($ultimaReasignacion) {
            return $ultimaReasignacion->usuario;
        }

        if ($tarea->creador_id === $tarea->responsable_id) {
            return null;
        }

        return $tarea->creador;
    }
}
