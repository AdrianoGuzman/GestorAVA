<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RetrocesoService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-12: el responsable o un colaborador devuelve una tarea En progreso
     * a Pendiente, sin cambiar responsable ni colaboradores (D4). Restringe
     * explicitamente el estado de origen a EnProgreso en vez de reutilizar
     * EstadoTarea::puedeTransicionarA(), que tambien permite Pendiente y
     * Cancelada como destino desde otros estados -- este metodo solo cubre
     * el caso puntual de RF-12.
     */
    public function retroceder(Tarea $tarea, User $solicitante, string $motivo): Tarea
    {
        if (! $this->permisos->puedeRetroceder($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                "Solo el responsable o un colaborador de la tarea puede retrocederla."
            );
        }

        if ($tarea->estado !== EstadoTarea::EnProgreso) {
            throw ValidationException::withMessages([
                "estado" => "Solo se puede retroceder una tarea que esté En progreso.",
            ]);
        }

        $tarea->update(["estado" => EstadoTarea::Pendiente]);

        $this->historial->registrar($tarea, TipoEvento::Retroceso, $solicitante, [
            "motivo" => $motivo,
        ]);

        $tarea = $tarea->fresh();

        if ($solicitante->id !== $tarea->responsable_id) {
            $this->notificaciones->notificarRetroceso($tarea->responsable, $tarea, $solicitante, $motivo);
        }

        return $tarea;
    }
}
