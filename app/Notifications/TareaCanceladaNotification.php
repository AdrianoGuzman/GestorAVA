<?php

namespace App\Notifications;

use App\Models\Tarea;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TareaCanceladaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tarea $tarea,
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
            ->subject("Se canceló una tarea: {$this->tarea->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("La tarea \"{$this->tarea->titulo}\", en la que participabas, fue cancelada por su responsable.")
            ->line("Motivo: {$this->motivo}")
            ->line('Gestor de Proyectos AVA');
    }
}
