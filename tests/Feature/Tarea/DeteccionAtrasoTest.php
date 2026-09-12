<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class DeteccionAtrasoTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_marca_como_atrasada_una_tarea_pendiente_con_fecha_vencida(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Pendiente,
            "esta_atrasada" => false,
            "fecha_compromiso" => now()->subDay(),
        ]);

        $this->artisan("tareas:marcar-atrasadas")->assertExitCode(0);

        $this->assertTrue($tarea->fresh()->esta_atrasada);
        $this->assertTrue(
            $tarea->historial()->where("tipo_evento", TipoEvento::TareaAtrasada)->exists()
        );
    }

    public function test_marca_como_atrasada_una_tarea_en_progreso_con_fecha_vencida(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::EnProgreso,
            "esta_atrasada" => false,
            "fecha_compromiso" => now()->subWeek(),
        ]);

        $this->artisan("tareas:marcar-atrasadas");

        $this->assertTrue($tarea->fresh()->esta_atrasada);
    }

    public function test_no_marca_una_tarea_cuya_fecha_de_compromiso_es_hoy(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Pendiente,
            "esta_atrasada" => false,
            "fecha_compromiso" => today(),
        ]);

        $this->artisan("tareas:marcar-atrasadas");

        $this->assertFalse($tarea->fresh()->esta_atrasada);
    }

    public function test_no_marca_una_tarea_con_fecha_de_compromiso_futura(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Pendiente,
            "esta_atrasada" => false,
            "fecha_compromiso" => now()->addWeek(),
        ]);

        $this->artisan("tareas:marcar-atrasadas");

        $this->assertFalse($tarea->fresh()->esta_atrasada);
    }

    public function test_no_marca_una_tarea_completada_aunque_este_vencida(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Completada,
            "esta_atrasada" => false,
            "fecha_compromiso" => now()->subWeek(),
        ]);

        $this->artisan("tareas:marcar-atrasadas");

        $this->assertFalse($tarea->fresh()->esta_atrasada);
    }

    public function test_no_marca_una_tarea_cancelada_aunque_este_vencida(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Cancelada,
            "esta_atrasada" => false,
            "fecha_compromiso" => now()->subWeek(),
        ]);

        $this->artisan("tareas:marcar-atrasadas");

        $this->assertFalse($tarea->fresh()->esta_atrasada);
    }

    public function test_no_duplica_el_historial_si_corre_dos_veces(): void
    {
        $tarea = Tarea::factory()->create([
            "estado" => EstadoTarea::Pendiente,
            "esta_atrasada" => false,
            "fecha_compromiso" => now()->subDay(),
        ]);

        $this->artisan("tareas:marcar-atrasadas");
        $this->artisan("tareas:marcar-atrasadas");

        $this->assertSame(
            1,
            $tarea->historial()->where("tipo_evento", TipoEvento::TareaAtrasada)->count()
        );
    }
}
