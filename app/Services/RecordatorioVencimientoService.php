<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Models\Tarea;

class RecordatorioVencimientoService
{
    private const DIAS_ANTICIPACION = 2;

    public function __construct(
        private readonly HistorialService $historial,
        private readonly NotificacionService $notificaciones,
    ) {
    }

    /**
     * RF-15: avisa al responsable y a los colaboradores cuando a una tarea
     * activa (pendiente o en progreso) le quedan exactamente 2 dias para su
     * fecha de compromiso. `recordatorio_vencimiento_enviado` evita que se
     * mande mas de una vez -- a diferencia de "atrasada" (que se mantiene
     * mientras la condicion sea verdadera), este es un aviso de una sola vez.
     */
    public function notificarProximasAVencer(): int
    {
        $fechaObjetivo = today()->addDays(self::DIAS_ANTICIPACION);

        $tareas = Tarea::whereIn("estado", [EstadoTarea::Pendiente, EstadoTarea::EnProgreso])
            ->where("recordatorio_vencimiento_enviado", false)
            ->whereDate("fecha_compromiso", $fechaObjetivo)
            ->with(["responsable", "colaboradores"])
            ->get();

        foreach ($tareas as $tarea) {
            $tarea->update(["recordatorio_vencimiento_enviado" => true]);

            $this->historial->registrar($tarea, TipoEvento::TareaProximaAVencer, null, [
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ]);

            $this->notificaciones->notificarProximoVencimiento($tarea->responsable, $tarea);

            foreach ($tarea->colaboradores as $colaborador) {
                $this->notificaciones->notificarProximoVencimiento($colaborador, $tarea);
            }
        }

        return $tareas->count();
    }
}
