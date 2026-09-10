<?php

namespace App\Services;

use App\Enums\TipoEvento;
use App\Models\HistorialTarea;
use App\Models\Tarea;
use App\Models\User;

class HistorialService
{
    /**
     * Registra un evento en el historial de la tarea (RF-16, RN-10).
     * $usuario null significa "Sistema" (evento automatico).
     */
    public function registrar(Tarea $tarea, TipoEvento $tipo, ?User $usuario, array $datos = []): HistorialTarea
    {
        return HistorialTarea::create([
            "tarea_id" => $tarea->id,
            "tipo_evento" => $tipo,
            "usuario_id" => $usuario?->id,
            "datos_evento" => $datos,
        ]);
    }
}
