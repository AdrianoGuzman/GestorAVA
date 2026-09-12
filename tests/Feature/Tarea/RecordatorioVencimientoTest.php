<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\User;
use App\Notifications\TareaProximaAVencerNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class RecordatorioVencimientoTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_notifica_cuando_faltan_exactamente_2_dias(): void
    {
        Notification::fake();

        $responsable = User::factory()->create();
        $colaborador = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "estado" => EstadoTarea::EnProgreso,
            "fecha_compromiso" => now()->addDays(2),
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        $this->artisan("tareas:notificar-proximas-vencer");

        $this->assertTrue($tarea->fresh()->recordatorio_vencimiento_enviado);
        $this->assertTrue(
            $tarea->historial()->where("tipo_evento", TipoEvento::TareaProximaAVencer)->exists()
        );
        Notification::assertSentTo($responsable, TareaProximaAVencerNotification::class);
        Notification::assertSentTo($colaborador, TareaProximaAVencerNotification::class);
    }

    public function test_no_notifica_si_falta_1_dia(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Pendiente,
            "fecha_compromiso" => now()->addDay(),
        ]);

        $this->artisan("tareas:notificar-proximas-vencer");

        $this->assertFalse($tarea->fresh()->recordatorio_vencimiento_enviado);
    }

    public function test_no_notifica_si_faltan_3_dias(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Pendiente,
            "fecha_compromiso" => now()->addDays(3),
        ]);

        $this->artisan("tareas:notificar-proximas-vencer");

        $this->assertFalse($tarea->fresh()->recordatorio_vencimiento_enviado);
    }

    public function test_no_notifica_una_tarea_completada(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Completada,
            "fecha_compromiso" => now()->addDays(2),
        ]);

        $this->artisan("tareas:notificar-proximas-vencer");

        $this->assertFalse($tarea->fresh()->recordatorio_vencimiento_enviado);
    }

    public function test_no_notifica_una_tarea_cancelada(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Cancelada,
            "fecha_compromiso" => now()->addDays(2),
        ]);

        $this->artisan("tareas:notificar-proximas-vencer");

        $this->assertFalse($tarea->fresh()->recordatorio_vencimiento_enviado);
    }

    public function test_no_notifica_dos_veces(): void
    {
        Notification::fake();

        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "estado" => EstadoTarea::Pendiente,
            "fecha_compromiso" => now()->addDays(2),
        ]);

        $this->artisan("tareas:notificar-proximas-vencer");
        $this->artisan("tareas:notificar-proximas-vencer");

        Notification::assertSentToTimes($responsable, TareaProximaAVencerNotification::class, 1);
    }
}
