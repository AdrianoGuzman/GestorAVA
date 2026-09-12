<?php

namespace App\Console\Commands;

use App\Services\DeteccionAtrasoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tareas:marcar-atrasadas')]
#[Description('RF-14: marca como atrasadas las tareas activas cuya fecha de compromiso ya paso.')]
class MarcarTareasAtrasadas extends Command
{
    public function handle(DeteccionAtrasoService $servicio): int
    {
        $cantidad = $servicio->marcarAtrasadas();

        $this->info("{$cantidad} tarea(s) marcada(s) como atrasada(s).");

        return self::SUCCESS;
    }
}
