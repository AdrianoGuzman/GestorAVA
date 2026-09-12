<?php

namespace App\Services;

use App\Models\Tarea;
use App\Models\User;

/**
 * RF-18 (Could): duplicar copia responsable de la tarea origen (y por lo
 * tanto su unidad organizacional, que TareaService::crear() deriva del
 * responsable); titulo, descripcion y fechas quedan editables. Reutiliza
 * TareaService::crear() en vez de tocarlo directamente, para que la
 * duplicada sea independiente -- historial propio desde cero y quien
 * duplica queda como creador, no el creador original.
 */
class DuplicarTareaService
{
    public function __construct(private readonly TareaService $tareaService)
    {
    }

    public function duplicar(Tarea $origen, array $datos, User $quienDuplica): Tarea
    {
        return $this->tareaService->crear([
            "titulo" => $datos["titulo"],
            "descripcion" => $datos["descripcion"] ?? null,
            "responsable_id" => $origen->responsable_id,
            "fecha_inicio" => $datos["fecha_inicio"] ?? null,
            "fecha_compromiso" => $datos["fecha_compromiso"],
        ], $quienDuplica);
    }
}
