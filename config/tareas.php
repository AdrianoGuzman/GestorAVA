<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Guards de completado (RF-11, D2)
    |--------------------------------------------------------------------------
    |
    | Clases que implementan App\Contracts\GuardCompletarTareaInterface,
    | consultadas antes de marcar una tarea como completada. Cada una revisa
    | una condicion de bloqueo propia de su modulo (dependencias, checklist,
    | etc.) sin que FinalizacionService conozca los detalles.
    |
    | Jeremy agrego App\Guards\ChecklistPendienteGuard::class (RF-23) y
    | App\Guards\DependenciasPendientesGuard::class (RF-22). Si sumas otro
    | guard, agregalo a esta misma lista -- append, no reemplazar el array.
    |
    */

    "guards_completar" => [
        \App\Guards\ChecklistPendienteGuard::class,
        \App\Guards\DependenciasPendientesGuard::class,
    ],

];
