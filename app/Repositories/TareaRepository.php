<?php

namespace App\Repositories;

use App\Models\Tarea;
use App\Repositories\Contracts\TareaRepositoryInterface;

class TareaRepository implements TareaRepositoryInterface
{
    public function crear(array $datos): Tarea
    {
        return Tarea::create($datos);
    }

    public function buscarPorId(int $id): ?Tarea
    {
        return Tarea::find($id);
    }
}
