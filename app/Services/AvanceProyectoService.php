<?php

namespace App\Services;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Models\Proyecto;
use App\Models\Seccion;
use Illuminate\Support\Collection;

/**
 * Calculo de avance/contadores de un Proyecto -- extraido de
 * ProyectoController para reusarlo tal cual en la exportacion PDF/Excel
 * (Franco, 14-09-2026), sin duplicar la formula del promedio ponderado.
 */
class AvanceProyectoService
{
    /**
     * Con secciones, el avance del proyecto es el promedio ponderado del
     * avance de cada seccion (peso 0-1, igual que la columna PONDERACIÓN de
     * las planillas de AVA) -- se normaliza dividiendo por la suma real de
     * los pesos, para no exigir que sumen exactamente 1. Las tareas sin
     * seccion no entran en esta cuenta (se ven, pero no ponderan). Sin
     * ninguna seccion todavia, cae al calculo simple de siempre sobre todas
     * las tareas del proyecto.
     */
    public function avanceProyecto(Proyecto $proyecto): float
    {
        if ($proyecto->secciones->isEmpty()) {
            return $this->avanceSimple($proyecto->tareas);
        }

        $pesoTotal = (float) $proyecto->secciones->sum("peso");

        if ($pesoTotal <= 0) {
            return 0.0;
        }

        $sumaPonderada = $proyecto->secciones->sum(
            fn (Seccion $seccion) => $seccion->peso * $this->avanceSimple($seccion->tareas)
        );

        return round($sumaPonderada / $pesoTotal, 4);
    }

    /** Avance simple (completadas / no-canceladas) de un conjunto de tareas -- es un proxy, no el progreso ponderado por subtarea (todavia sin definir). */
    public function avanceSimple(Collection $tareas): float
    {
        $noCanceladas = $tareas->where("estado", "!=", EstadoTarea::Cancelada);

        if ($noCanceladas->isEmpty()) {
            return 0.0;
        }

        return round($noCanceladas->where("estado", EstadoTarea::Completada)->count() / $noCanceladas->count(), 4);
    }

    /** Mismo shape que MisTareasService::obtener()['contadores'], acotado a las tareas de un proyecto. */
    public function contadoresDeTareas(Collection $tareas): array
    {
        return [
            "total" => $tareas->count(),
            "atrasadas" => $tareas->where("esta_atrasada", true)->count(),
            "en_progreso" => $tareas->where("estado", EstadoTarea::EnProgreso)->count(),
            "pendientes" => $tareas->where("estado", EstadoTarea::Pendiente)->count(),
            "completadas" => $tareas->where("estado", EstadoTarea::Completada)->count(),
            "prioridad_alta" => $tareas->where("prioridad", PrioridadTarea::Alta)->count(),
        ];
    }
}
