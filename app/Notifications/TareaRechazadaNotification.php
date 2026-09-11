<?php

namespace App\Notifications;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TareaRechazadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tarea $tarea,
        private readonly User $quienRechaza,
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
            ->subject("Rechazaron una tarea: {$this->tarea->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("{$this->quienRechaza->name} rechazó la tarea \"{$this->tarea->titulo}\" que le asignaste.")
            ->line("Motivo: {$this->motivo}")
            ->line("Queda a la espera de que la corrijas y la reasignes.")
            ->action('Ver tarea', url("/tareas/{$this->tarea->id}"))
            ->line('Gestor de Proyectos AVA');
    }
}
