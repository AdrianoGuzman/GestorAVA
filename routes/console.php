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
