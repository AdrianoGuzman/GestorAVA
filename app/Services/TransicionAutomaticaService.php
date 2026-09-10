<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Support\Carbon;

class TransicionAutomaticaService
{
    public function __construct(
        private readonly HistorialService $historial,
    ) {
    }

    /**
     * RF-10: al abrir el detalle de una tarea Pendiente, si quien la abre es
     * el responsable o un colaborador y ya llegó (o no tiene) fecha_inicio,
     * el sistema la pasa a En progreso automáticamente.
     */
    public function procesarApertura(Tarea $tarea, User $usuario): Tarea
    {
        if ($tarea->estado !== EstadoTarea::Pendiente) {
            return $tarea;
        }

        $esResponsableOColaborador = $usuario->id === $tarea->responsable_id
            || $tarea->colaboradores->contains("id", $usuario->id);

        if (! $esResponsableOColaborador) {
            return $tarea;
        }

        if ($tarea->fecha_inicio !== null && Carbon::today()->lt($tarea->fecha_inicio)) {
            return $tarea;
        }

        $tarea->update(["estado" => EstadoTarea::EnProgreso]);

        $this->historial->registrar($tarea, TipoEvento::TransicionAutomatica, $usuario, [
            "estado_anterior" => EstadoTarea::Pendiente->value,
            "estado_nuevo" => EstadoTarea::EnProgreso->value,
        ]);

        return $tarea->fresh();
    }
}
