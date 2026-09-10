<?php

namespace App\Notifications;

use App\Models\Tarea;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TareaAsignadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tarea $tarea,
        private readonly string $rol,
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
            ->subject("Te asignaron una tarea: {$this->tarea->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("Se te asignó como {$this->rol} de la tarea \"{$this->tarea->titulo}\".")
            ->line("Fecha de compromiso: {$this->tarea->fecha_compromiso->format('d-m-Y')}.")
            ->action('Ver tarea', url("/tareas/{$this->tarea->id}"))
            ->line('Gestor de Proyectos AVA');
    }
}
