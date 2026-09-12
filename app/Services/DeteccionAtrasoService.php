<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Models\Tarea;

class DeteccionAtrasoService
{
    public function __construct(
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-14: marca como atrasadas las tareas activas (pendiente o en
     * progreso) cuya fecha de compromiso ya paso por completo -- "atrasada"
     * es un indicador independiente del estado (RN, ver docs de diseño), no
     * una transicion, asi que esto nunca toca `estado`. Una tarea recien
     * queda atrasada al dia SIGUIENTE de su fecha_compromiso, no el mismo
     * dia (fecha_compromiso es una fecha calendario sin hora). Avisa al
     * responsable y a los colaboradores -- si no, la marca queda muda y
     * nadie se entera sin entrar a mirar la tarea.
     */
    public function marcarAtrasadas(): int
    {
        $tareas = Tarea::whereIn("estado", [EstadoTarea::Pendiente, EstadoTarea::EnProgreso])
            ->where("esta_atrasada", false)
            ->where("fecha_compromiso", "<", today())
            ->with(["responsable", "colaboradores"])
            ->get();

        foreach ($tareas as $tarea) {
            $tarea->update(["esta_atrasada" => true]);

            $this->historial->registrar($tarea, TipoEvento::TareaAtrasada, null, [
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ]);

            $this->notificaciones->notificarAtraso($tarea->responsable, $tarea);

            foreach ($tarea->colaboradores as $colaborador) {
                $this->notificaciones->notificarAtraso($colaborador, $tarea);
            }
        }

        return $tareas->count();
    }
}
