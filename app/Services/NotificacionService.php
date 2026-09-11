<?php

namespace App\Services;

use App\Enums\TipoNotificacion;
use App\Models\Tarea;
use App\Models\User;
use App\Notifications\TareaAsignadaNotification;
use App\Notifications\TareaCanceladaNotification;
use App\Notifications\TareaRechazadaNotification;
use App\Notifications\TareaRetrocedidaNotification;

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

    /**
     * RF-12 D5: si quien retrocede la tarea es un colaborador, se notifica
     * al responsable principal.
     */
    public function notificarRetroceso(User $responsable, Tarea $tarea, User $colaborador, string $motivo): void
    {
        $responsable->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Retroceso,
            "mensaje" => "{$colaborador->name} retrocedió la tarea \"{$tarea->titulo}\" a Pendiente.",
        ]);

        $responsable->notify(new TareaRetrocedidaNotification($tarea, $colaborador, $motivo));
    }

    /**
     * RF-13 D5 / RF-17 D4: notifica al usuario que debe corregir y reasignar
     * la tarea (quien la delegó por última vez) cuando esta es rechazada.
     */
    public function notificarRechazo(User $delegador, Tarea $tarea, User $quienRechaza, string $motivo): void
    {
        $delegador->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Rechazo,
            "mensaje" => "{$quienRechaza->name} rechazó la tarea \"{$tarea->titulo}\".",
        ]);

        $delegador->notify(new TareaRechazadaNotification($tarea, $quienRechaza, $motivo));
    }

    /**
     * RF-25: notifica a un colaborador que la tarea en la que participaba
     * fue cancelada por el responsable principal.
     */
    public function notificarCancelacion(User $colaborador, Tarea $tarea, string $motivo): void
    {
        $colaborador->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Cancelacion,
            "mensaje" => "Se canceló la tarea \"{$tarea->titulo}\".",
        ]);

        $colaborador->notify(new TareaCanceladaNotification($tarea, $motivo));
    }
}
