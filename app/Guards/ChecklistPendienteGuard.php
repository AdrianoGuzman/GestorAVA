<?php

namespace App\Guards;

use App\Contracts\GuardCompletarTareaInterface;
use App\Models\Tarea;

/**
 * RF-11 (D2) / RF-23: bloquea completar la tarea si el checklist compartido
 * tiene items sin marcar. El checklist compartido solo aplica cuando la
 * tarea tiene colaboradores (ver ContextoProgramacion.md) -- si no los
 * tiene, checklistItems() esta vacia y este guard no bloquea nada.
 */
class ChecklistPendienteGuard implements GuardCompletarTareaInterface
{
    public function verificar(Tarea $tarea): array
    {
        $pendientes = $tarea->checklistItems()->where("completado", false)->count();

        if ($pendientes === 0) {
            return [];
        }

        return [
            $pendientes === 1
                ? "Queda 1 ítem del checklist sin marcar."
                : "Quedan {$pendientes} ítems del checklist sin marcar.",
        ];
    }
}
