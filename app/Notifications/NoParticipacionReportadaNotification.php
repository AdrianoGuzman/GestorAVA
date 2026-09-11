<?php

namespace App\Notifications;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NoParticipacionReportadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tarea $tarea,
        private readonly User $quienReporta,
        private readonly string $motivo,
    ) {
        $this->onQueue('cola');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Alguien no puede seguir en una tarea: {$this->tarea->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("{$this->quienReporta->name} avisó que no puede o no quiere seguir participando en la tarea \"{$this->tarea->titulo}\".")
            ->line("Motivo: {$this->motivo}")
            ->line("La tarea sigue igual por ahora; este es solo un aviso para que decidas cómo seguir.")
            ->action('Ver tarea', url("/tareas/{$this->tarea->id}"))
            ->line('Gestor de Proyectos AVA');
    }
}
