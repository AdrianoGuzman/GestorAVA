<?php

namespace App\Services;

use App\Enums\TipoNotificacion;
use App\Models\Tarea;
use App\Models\User;
use App\Notifications\TareaAsignadaNotification;

class NotificacionService
{
    /**
     * Notifica a un usuario que fue asignado como responsable o colaborador
     * de una tarea (RF-17, D1). Deja registro in-app y envia el correo.
     */
    public function notificarAsignacion(User $destinatario, Tarea $tarea, string $rol): void
    {
        $destinatario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Delegacion,
            "mensaje" => "Se te asignó como {$rol} de la tarea \"{$tarea->titulo}\".",
        ]);

        $destinatario->notify(new TareaAsignadaNotification($tarea, $rol));
    }
}
