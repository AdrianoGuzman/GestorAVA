<?php

namespace App\Guards;

use App\Contracts\GuardCompletarTareaInterface;
use App\Enums\EstadoTarea;
use App\Models\Tarea;

/**
 * RF-11 (D2) / RF-22: bloquea completar la tarea padre si tiene tareas hijas
 * que todavia no llegaron a un estado terminal. Completada y Cancelada no
 * bloquean -- una hija cancelada ya no es trabajo pendiente.
 */
class DependenciasPendientesGuard implements GuardCompletarTareaInterface
{
    public function verificar(Tarea $tarea): array
    {
        $pendientes = $tarea->tareasHijas()
            ->whereNotIn("estado", [EstadoTarea::Completada, EstadoTarea::Cancelada])
            ->count();

        if ($pendientes === 0) {
            return [];
        }

        return [
            $pendientes === 1
                ? "Queda 1 tarea hija sin completar."
                : "Quedan {$pendientes} tareas hijas sin completar.",
        ];
    }
}
