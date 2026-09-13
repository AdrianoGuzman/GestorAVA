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
     * responsable. Decision de Franco (13-09-2026): si el responsable es
     * tambien el creador, la accion ni siquiera queda disponible (ver
     * PermisosService::puedeReportarProblema()) -- notificarse a uno mismo
     * no tiene sentido, y esa persona ya puede editar la tarea directamente.
     */
    public function reportar(Tarea $tarea, User $solicitante, string $motivo): void
    {
        if (! $this->permisos->puedeReportarProblema($tarea, $solicitante)) {
            $mensaje = match (true) {
                $tarea->estado->esTerminal() => "No se puede reportar un problema en una tarea completada o cancelada.",
                $solicitante->id === $tarea->responsable_id && $tarea->responsable_id === $tarea->creador_id =>
                    "Sos el creador y el responsable de esta tarea -- si algo está mal definido, corregilo editando la tarea.",
                default => "Solo el responsable o un colaborador de la tarea puede reportar un problema.",
            };

            throw new PermisoDenegadoException($mensaje);
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
