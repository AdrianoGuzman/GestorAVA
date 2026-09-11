<?php

namespace App\Notifications;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProblemaReportadoNotification extends Notification implements ShouldQueue
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
            ->subject("Reportaron un problema en una tarea: {$this->tarea->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("{$this->quienReporta->name} reportó que la tarea \"{$this->tarea->titulo}\" está mal definida.")
            ->line("Motivo: {$this->motivo}")
            ->line("La tarea sigue su curso normal; este es solo un aviso para que la corrijas.")
            ->action('Ver tarea', url("/tareas/{$this->tarea->id}"))
            ->line('Gestor de Proyectos AVA');
    }
}
