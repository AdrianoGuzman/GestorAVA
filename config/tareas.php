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
    | Ej: Oscar (RF-22) agrega App\Guards\DependenciasPendientesGuard::class,
    |     Jeremy (RF-23) agrega App\Guards\ChecklistPendienteGuard::class.
    |
    */

    "guards_completar" => [
        \App\Guards\ChecklistPendienteGuard::class,
    ],

];
