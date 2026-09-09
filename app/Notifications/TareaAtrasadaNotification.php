<?php

namespace App\Notifications;

use App\Models\Tarea;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TareaAtrasadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tarea $tarea,
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
            ->subject("Tarea atrasada: {$this->tarea->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("La tarea \"{$this->tarea->titulo}\" superó su fecha de compromiso ({$this->tarea->fecha_compromiso->format('d-m-Y')}) sin completarse.")
            ->action('Ver tarea', url("/tareas/{$this->tarea->id}"))
            ->line('Gestor de Proyectos AVA');
    }
}
