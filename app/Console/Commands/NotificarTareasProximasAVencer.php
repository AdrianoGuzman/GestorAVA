<?php

namespace App\Console\Commands;

use App\Services\RecordatorioVencimientoService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tareas:notificar-proximas-vencer')]
#[Description('RF-15: avisa al responsable/colaboradores cuando a una tarea le quedan 2 dias para vencer.')]
class NotificarTareasProximasAVencer extends Command
{
    public function handle(RecordatorioVencimientoService $servicio): int
    {
        $cantidad = $servicio->notificarProximasAVencer();

        $this->info("{$cantidad} tarea(s) notificada(s) por proximo vencimiento.");

        return self::SUCCESS;
    }
}
