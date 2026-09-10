<?php

namespace Tests\Support;

use App\Contracts\GuardCompletarTareaInterface;
use App\Models\Tarea;

/**
 * Guard falso usado solo en tests para verificar que FinalizacionService
 * realmente consulta config("tareas.guards_completar") antes de completar.
 */
class GuardDeBloqueoDePruebas implements GuardCompletarTareaInterface
{
    public function verificar(Tarea $tarea): array
    {
        return $tarea->titulo === "BLOQUEADA_TEST" ? ["Motivo de prueba"] : [];
    }
}
