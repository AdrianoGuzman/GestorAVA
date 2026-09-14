<?php

namespace App\Notifications;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DependenciaCreadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tarea $tareaPadre,
        private readonly Tarea $tareaHija,
        private readonly User $creador,
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
            ->subject("Nueva dependencia en tu tarea: {$this->tareaPadre->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("{$this->creador->name} creó la tarea \"{$this->tareaHija->titulo}\" como dependencia de \"{$this->tareaPadre->titulo}\".")
            ->action('Ver tarea', url("/tareas/{$this->tareaPadre->id}"))
            ->line('Gestor de Proyectos AVA');
    }
}
