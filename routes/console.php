<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// RF-14: revisa cada hora si alguna tarea activa paso su fecha de
// compromiso y la marca como atrasada. El worker-schedule del docker
// compose ya corre "php artisan schedule:work" -- no hace falta tocar
// infraestructura, esto empieza a correr solo.
Schedule::command('tareas:marcar-atrasadas')->hourly()->withoutOverlapping();

// RF-15: aviso de una sola vez cuando a una tarea activa le quedan 2 dias
// para su fecha de compromiso (recordatorio_vencimiento_enviado evita que
// se repita). Corre una vez al dia por la manana, no hace falta cada hora
// porque el aviso no cambia durante el dia.
Schedule::command('tareas:notificar-proximas-vencer')->dailyAt('08:00')->withoutOverlapping();
