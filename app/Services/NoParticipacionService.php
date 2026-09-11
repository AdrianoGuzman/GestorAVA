<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;

class NoParticipacionService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * El responsable o un colaborador avisa que no puede o no quiere seguir
     * participando en la tarea, con motivo obligatorio. Igual que reportar
     * un problema: NO cambia el estado ni quita a nadie de la tarea, solo
     * registra el evento y notifica a quien puede decidir qué hacer (el
     * creador si avisa el responsable, el responsable si avisa un
     * colaborador). Sin notificación si el destinatario es el mismo que
     * avisa.
     */
    public function reportar(Tarea $tarea, User $solicitante, string $motivo): void
    {
        if (! $this->permisos->puedeReportarNoParticipacion($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                "Solo el responsable o un colaborador de la tarea puede avisar que no puede participar."
            );
        }

        $this->historial->registrar($tarea, TipoEvento::NoParticipacionReportada, $solicitante, [
            "motivo" => $motivo,
        ]);

        $destinatario = $this->obtenerDestinatario($tarea, $solicitante);

        if ($destinatario && $destinatario->id !== $solicitante->id) {
            $this->notificaciones->notificarNoParticipacion($destinatario, $tarea, $solicitante, $motivo);
        }
    }

    private function obtenerDestinatario(Tarea $tarea, User $solicitante): ?User
    {
        if ($solicitante->id === $tarea->responsable_id) {
            return $tarea->creador;
        }

        return $tarea->responsable;
    }
}
