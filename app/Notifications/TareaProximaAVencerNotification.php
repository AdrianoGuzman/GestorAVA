<?php

namespace App\Notifications;

use App\Models\Tarea;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TareaProximaAVencerNotification extends Notification implements ShouldQueue
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
            ->subject("Tu tarea \"{$this->tarea->titulo}\" está por vencer")
            ->greeting("Hola {$notifiable->name},")
            ->line("La tarea \"{$this->tarea->titulo}\" vence el {$this->tarea->fecha_compromiso->format('d-m-Y')}.")
            ->action('Ver tarea', url("/tareas/{$this->tarea->id}"))
            ->line('Gestor de Proyectos AVA');
    }
}
