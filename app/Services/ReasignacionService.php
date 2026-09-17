<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Exceptions\PermisoDenegadoException;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReasignacionService
{
    public function __construct(
        private readonly PermisosService $permisos,
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-05: camino firme, solo responsable actual o su superior directo de
     * unidad (RN-11).
     */
    public function reasignar(Tarea $tarea, User $nuevoResponsable, User $solicitante, bool $mantenerComoColaborador): Tarea
    {
        if (! $this->permisos->puedeReasignar($tarea, $solicitante)) {
            throw new PermisoDenegadoException(
                $tarea->estado->esTerminal()
                    ? "No se puede reasignar una tarea completada o cancelada."
                    : "Solo el responsable actual o su superior jerarquico directo de la misma unidad puede reasignar esta tarea."
            );
        }

        return $this->ejecutar($tarea, $nuevoResponsable, $solicitante, $mantenerComoColaborador, TipoEvento::Reasignacion, []);
    }

    /**
     * RN-12: reasignacion excepcional cuando el responsable y su superior
     * directo de unidad estan ambos indisponibles. Requiere motivo y queda
     * registrada distinta de una reasignacion normal.
     */
    public function reasignarComoExcepcion(Tarea $tarea, User $nuevoResponsable, User $autorizador, string $motivo, bool $mantenerComoColaborador): Tarea
    {
        if (! $this->permisos->puedeAutorizarExcepcion($tarea, $autorizador)) {
            throw new PermisoDenegadoException(
                $tarea->estado->esTerminal()
                    ? "No se puede reasignar una tarea completada o cancelada."
                    : "Solo un usuario de nivel jerarquico superior al del responsable puede autorizar esta excepcion."
            );
        }

        return $this->ejecutar($tarea, $nuevoResponsable, $autorizador, $mantenerComoColaborador, TipoEvento::ReasignacionExcepcional, [
            "motivo_excepcion" => $motivo,
        ]);
    }

    private function ejecutar(
        Tarea $tarea,
        User $nuevoResponsable,
        User $solicitante,
        bool $mantenerComoColaborador,
        TipoEvento $tipoEvento,
        array $datosExtra,
    ): Tarea {
        $responsableSaliente = $tarea->responsable;

        DB::connection("usuarios")->transaction(function () use ($tarea, $nuevoResponsable, $responsableSaliente, $mantenerComoColaborador) {
            $tarea->update(["responsable_id" => $nuevoResponsable->id]);

            // Si el nuevo responsable ya era colaborador (se lo "asciende"),
            // sacarlo de esa lista -- no puede figurar como responsable Y
            // como colaborador al mismo tiempo, queda duplicado en la UI.
            $tarea->colaboradores()->detach($nuevoResponsable->id);

            if ($mantenerComoColaborador && $responsableSaliente && $responsableSaliente->id !== $nuevoResponsable->id) {
                $tarea->colaboradores()->syncWithoutDetaching([$responsableSaliente->id]);
            }
        });

        $this->historial->registrar($tarea, $tipoEvento, $solicitante, array_merge([
            "responsable_anterior_id" => $responsableSaliente?->id,
            "responsable_nuevo_id" => $nuevoResponsable->id,
            "mantuvo_como_colaborador" => $mantenerComoColaborador,
        ], $datosExtra));

        if ($nuevoResponsable->id !== $solicitante->id) {
            $this->notificaciones->notificarAsignacion($nuevoResponsable, $tarea->fresh(), "responsable");
        }

        return $tarea->fresh();
    }
}
