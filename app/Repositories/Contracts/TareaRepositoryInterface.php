<?php

namespace App\Repositories\Contracts;

use App\Models\Tarea;

interface TareaRepositoryInterface
{
    public function crear(array $datos): Tarea;

    public function buscarPorId(int $id): ?Tarea;
}
