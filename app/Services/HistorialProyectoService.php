<?php

namespace App\Services;

use App\Enums\TipoEventoProyecto;
use App\Models\HistorialProyecto;
use App\Models\Proyecto;
use App\Models\User;

class HistorialProyectoService
{
    /** Registra un evento en el historial del proyecto (trazabilidad, Franco 14-09-2026). $usuario null significa "Sistema". */
    public function registrar(Proyecto $proyecto, TipoEventoProyecto $tipo, ?User $usuario, array $datos = []): HistorialProyecto
    {
        return HistorialProyecto::create([
            "proyecto_id" => $proyecto->id,
            "tipo_evento" => $tipo,
            "usuario_id" => $usuario?->id,
            "datos_evento" => $datos,
        ]);
    }
}
