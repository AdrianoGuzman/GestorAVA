<?php

namespace App\Notifications;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TareaRetrocedidaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Tarea $tarea,
        private readonly User $colaborador,
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
            ->subject("Retrocedieron una tarea a Pendiente: {$this->tarea->titulo}")
            ->greeting("Hola {$notifiable->name},")
            ->line("{$this->colaborador->name} retrocedió la tarea \"{$this->tarea->titulo}\" de En progreso a Pendiente.")
            ->line("Motivo: {$this->motivo}")
            ->action('Ver tarea', url("/tareas/{$this->tarea->id}"))
            ->line('Gestor de Proyectos AVA');
    }
}
