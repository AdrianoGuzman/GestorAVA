<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\PrioridadTarea;
use App\Enums\TipoEvento;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class EditarTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_el_responsable_puede_editar_y_registra_el_historial(): void
    {
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "titulo" => "Titulo con error",
            "descripcion" => "Descripcion vieja",
        ]);

        $response = $this->actingAs($responsable)->patch("/tareas/{$tarea->id}", [
            "titulo" => "Titulo corregido",
            "descripcion" => "Descripcion nueva",
            "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas("success");

        $tarea->refresh();
        $this->assertSame("Titulo corregido", $tarea->titulo);
        $this->assertSame("Descripcion nueva", $tarea->descripcion);
        $this->assertTrue(
            $tarea->historial()->where("tipo_evento", TipoEvento::TareaEditada)->exists()
        );
    }

    public function test_el_creador_puede_editar_aunque_no_sea_el_responsable(): void
    {
        $creador = User::factory()->create();
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "creador_id" => $creador->id,
            "responsable_id" => $responsable->id,
        ]);

        $this->actingAs($creador)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "Nuevo titulo",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHas("success");

        $this->assertSame("Nuevo titulo", $tarea->fresh()->titulo);
    }

    public function test_un_colaborador_no_puede_editar(): void
    {
        $responsable = User::factory()->create();
        $colaborador = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "titulo" => "Titulo original",
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        $this->actingAs($colaborador)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "Intento de edicion",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHas("error");

        $this->assertSame("Titulo original", $tarea->fresh()->titulo);
    }

    public function test_un_usuario_ajeno_no_puede_editar(): void
    {
        $responsable = User::factory()->create();
        $ajeno = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "titulo" => "Titulo original",
        ]);

        $this->actingAs($ajeno)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "Intento de edicion",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHas("error");

        $this->assertSame("Titulo original", $tarea->fresh()->titulo);
    }

    public function test_no_se_puede_editar_una_tarea_completada(): void
    {
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "estado" => EstadoTarea::Completada,
            "titulo" => "Titulo original",
        ]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "Intento de edicion",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHasErrors("titulo");

        $this->assertSame("Titulo original", $tarea->fresh()->titulo);
    }

    public function test_no_se_puede_editar_una_tarea_cancelada(): void
    {
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "estado" => EstadoTarea::Cancelada,
            "titulo" => "Titulo original",
        ]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "Intento de edicion",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHasErrors("titulo");

        $this->assertSame("Titulo original", $tarea->fresh()->titulo);
    }

    public function test_corregir_la_fecha_a_una_no_vencida_le_saca_el_atraso(): void
    {
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "estado" => EstadoTarea::EnProgreso,
            "esta_atrasada" => true,
            "fecha_compromiso" => now()->subDay(),
        ]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => $tarea->titulo,
                "fecha_compromiso" => now()->addWeek()->toDateString(),
            ])
            ->assertSessionHas("success");

        $this->assertFalse($tarea->fresh()->esta_atrasada);
    }

    public function test_el_titulo_es_obligatorio(): void
    {
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->create(["responsable_id" => $responsable->id]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHasErrors("titulo");
    }

    public function test_permite_editar_aunque_la_fecha_ya_este_vencida_sin_cambiarla(): void
    {
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "estado" => EstadoTarea::EnProgreso,
            "esta_atrasada" => true,
            "fecha_compromiso" => now()->subWeek(),
            "titulo" => "Titulo con typo",
        ]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "Titulo sin typo",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHas("success");

        $tarea->refresh();
        $this->assertSame("Titulo sin typo", $tarea->titulo);
        $this->assertTrue($tarea->esta_atrasada);
    }

    public function test_permite_cambiar_la_prioridad_al_editar(): void
    {
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->prioridadMedia()->create(["responsable_id" => $responsable->id]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => $tarea->titulo,
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
                "prioridad" => "alta",
            ])
            ->assertSessionHas("success");

        $this->assertSame(PrioridadTarea::Alta, $tarea->fresh()->prioridad);
    }

    public function test_conserva_la_prioridad_si_no_se_manda_al_editar(): void
    {
        $responsable = User::factory()->create();
        $tarea = Tarea::factory()->prioridadAlta()->create(["responsable_id" => $responsable->id]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "Solo corrijo el titulo",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHas("success");

        $this->assertSame(PrioridadTarea::Alta, $tarea->fresh()->prioridad);
    }

    public function test_permite_asociar_o_cambiar_el_proyecto_al_editar(): void
    {
        // Directorio: asociar una tarea a un proyecto esta reservado a
        // Directorio/Gerencia, ver test_un_responsable_sin_permiso_no_puede...
        $responsable = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $proyecto = Proyecto::factory()->create();
        $tarea = Tarea::factory()->create(["responsable_id" => $responsable->id]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => $tarea->titulo,
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
                "proyecto_id" => $proyecto->id,
            ])
            ->assertSessionHas("success");

        $this->assertSame($proyecto->id, $tarea->fresh()->proyecto_id);
    }

    public function test_conserva_el_proyecto_si_no_se_manda_al_editar(): void
    {
        $responsable = User::factory()->create();
        $proyecto = Proyecto::factory()->create();
        $tarea = Tarea::factory()->create(["responsable_id" => $responsable->id, "proyecto_id" => $proyecto->id]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => "Solo corrijo el titulo",
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
            ])
            ->assertSessionHas("success");

        $this->assertSame($proyecto->id, $tarea->fresh()->proyecto_id);
    }

    public function test_un_responsable_sin_permiso_no_puede_cambiar_el_proyecto_aunque_lo_mande(): void
    {
        $responsable = User::factory()->create();
        $proyecto = Proyecto::factory()->create();
        $tarea = Tarea::factory()->create(["responsable_id" => $responsable->id, "proyecto_id" => null]);

        $this->actingAs($responsable)
            ->patch("/tareas/{$tarea->id}", [
                "titulo" => $tarea->titulo,
                "fecha_compromiso" => $tarea->fecha_compromiso->toDateString(),
                "proyecto_id" => $proyecto->id,
            ])
            ->assertSessionHas("success");

        $this->assertNull($tarea->fresh()->proyecto_id);
    }
}
