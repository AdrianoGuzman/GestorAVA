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
     * colaborador). Decision de Franco (13-09-2026): si el responsable es
     * tambien el creador, la accion ni siquiera queda disponible (ver
     * PermisosService::puedeReportarNoParticipacion()) -- no hay a quien
     * avisar, y esa persona ya puede reasignar la tarea si no puede seguir.
     */
    public function reportar(Tarea $tarea, User $solicitante, string $motivo): void
    {
        if (! $this->permisos->puedeReportarNoParticipacion($tarea, $solicitante)) {
            $mensaje = match (true) {
                $tarea->estado->esTerminal() => "No se puede avisar sobre una tarea completada o cancelada.",
                $solicitante->id === $tarea->responsable_id && $tarea->responsable_id === $tarea->creador_id =>
                    "Sos el creador y el responsable de esta tarea -- si no podés seguir con ella, reasignala.",
                default => "Solo el responsable o un colaborador de la tarea puede avisar que no puede participar.",
            };

            throw new PermisoDenegadoException($mensaje);
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
