<?php

namespace App\Contracts;

use App\Models\Tarea;

/**
 * Punto de extension de RF-11 (D2): cada modulo que necesite bloquear el
 * completado de una tarea (ej. RF-22 dependencias, RF-23 checklist) implementa
 * esta interfaz y se registra en config/tareas.php ("guards_completar"), sin
 * tocar FinalizacionService.
 */
interface GuardCompletarTareaInterface
{
    /**
     * @return array<int, string> motivos de bloqueo; vacio si no hay ninguno.
     */
    public function verificar(Tarea $tarea): array;
}
