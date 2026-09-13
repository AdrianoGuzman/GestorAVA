<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;

class ReporteProblemaService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-13 (rediseñado): el responsable o un colaborador reporta que la
     * tarea está mal definida, con motivo obligatorio. A diferencia del
     * "Rechazo" original, esto NO cambia el estado de la tarea -- el
     * trabajo sigue su curso normal, solo se registra en el historial y se
     * notifica a quien puede corregir la definición: si reporta el
     * responsable, va al creador; si reporta un colaborador, va al
     * responsable. Sin destinatario (y sin notificación) si esa persona es
     * el mismo que reporta -- ej. el responsable creó su propia tarea.
     */
    public function reportar(Tarea $tarea, User $solicitante, string $motivo): void
    {
        if (! $this->permisos->puedeReportarProblema($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                $tarea->estado->esTerminal()
                    ? "No se puede reportar un problema en una tarea completada o cancelada."
                    : "Solo el responsable o un colaborador de la tarea puede reportar un problema."
            );
        }

        $this->historial->registrar($tarea, TipoEvento::ProblemaReportado, $solicitante, [
            "motivo" => $motivo,
        ]);

        $destinatario = $this->obtenerDestinatario($tarea, $solicitante);

        if ($destinatario && $destinatario->id !== $solicitante->id) {
            $this->notificaciones->notificarProblemaReportado($destinatario, $tarea, $solicitante, $motivo);
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
