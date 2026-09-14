<?php

namespace App\Services;

use App\Enums\TipoNotificacion;
use App\Models\Notificacion;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use App\Notifications\DependenciaCreadaNotification;
use App\Notifications\NoParticipacionReportadaNotification;
use App\Notifications\ProblemaReportadoNotification;
use App\Notifications\TareaAsignadaNotification;
use App\Notifications\TareaAtrasadaNotification;
use App\Notifications\TareaCanceladaNotification;
use App\Notifications\TareaProximaAVencerNotification;
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
     * RF-13 (rediseñado): notifica a quien puede corregir la definición de
     * la tarea -- el creador si reporta el responsable, o el responsable si
     * reporta un colaborador -- sin cambiar el estado de la tarea.
     */
    public function notificarProblemaReportado(User $destinatario, Tarea $tarea, User $quienReporta, string $motivo): void
    {
        $destinatario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::ProblemaReportado,
            "mensaje" => "{$quienReporta->name} reportó un problema en la tarea \"{$tarea->titulo}\".",
        ]);

        $destinatario->notify(new ProblemaReportadoNotification($tarea, $quienReporta, $motivo));
    }

    /**
     * El responsable o un colaborador avisa que no puede/quiere seguir
     * participando. Mismo destinatario que reportar problema: el creador si
     * avisa el responsable, el responsable si avisa un colaborador.
     */
    public function notificarNoParticipacion(User $destinatario, Tarea $tarea, User $quienReporta, string $motivo): void
    {
        $destinatario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::NoParticipacionReportada,
            "mensaje" => "{$quienReporta->name} avisó que no puede seguir en la tarea \"{$tarea->titulo}\".",
        ]);

        $destinatario->notify(new NoParticipacionReportadaNotification($tarea, $quienReporta, $motivo));
    }

    /**
     * RF-21/RF-22: avisa al responsable de la tarea padre que se creó una
     * tarea hija (dependencia) a partir de su tarea. Sin notificación si el
     * mismo responsable fue quien creó la dependencia.
     */
    public function notificarDependenciaCreada(User $responsablePadre, Tarea $tareaPadre, Tarea $tareaHija, User $creador): void
    {
        $responsablePadre->notificacionesRecibidas()->create([
            "tarea_id" => $tareaPadre->id,
            "tipo" => TipoNotificacion::DependenciaCreada,
            "mensaje" => "{$creador->name} creó la tarea \"{$tareaHija->titulo}\" como dependencia de \"{$tareaPadre->titulo}\".",
        ]);

        $responsablePadre->notify(new DependenciaCreadaNotification($tareaPadre, $tareaHija, $creador));
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

    /**
     * RF-14: avisa al responsable o a un colaborador que la tarea paso su
     * fecha de compromiso y quedo marcada como atrasada.
     */
    public function notificarAtraso(User $destinatario, Tarea $tarea): void
    {
        $destinatario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Atraso,
            "mensaje" => "La tarea \"{$tarea->titulo}\" quedó marcada como atrasada.",
        ]);

        $destinatario->notify(new TareaAtrasadaNotification($tarea));
    }

    /**
     * RF-15: avisa al responsable o a un colaborador que a la tarea le
     * quedan pocos dias para su fecha de compromiso.
     */
    public function notificarProximoVencimiento(User $destinatario, Tarea $tarea): void
    {
        $destinatario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::ProximoVencimiento,
            "mensaje" => "La tarea \"{$tarea->titulo}\" está por vencer.",
        ]);

        $destinatario->notify(new TareaProximaAVencerNotification($tarea));
    }

    /**
     * RF-15/D1: campana de notificaciones -- ultimas recibidas por el
     * usuario, para mostrar en la plataforma sin que tenga que consultarlas
     * entrando tarea por tarea.
     */
    public function recientesDe(User $usuario, int $limite = 15): Collection
    {
        return $usuario->notificacionesRecibidas()
            ->with("tarea:id,titulo")
            ->latest("created_at")
            ->limit($limite)
            ->get();
    }

    public function marcarLeida(Notificacion $notificacion, User $usuario): void
    {
        abort_unless($notificacion->usuario_id === $usuario->id, 403);

        $notificacion->update(["leida" => true]);
    }

    public function marcarTodasLeidas(User $usuario): void
    {
        $usuario->notificacionesRecibidas()->where("leida", false)->update(["leida" => true]);
    }
}
