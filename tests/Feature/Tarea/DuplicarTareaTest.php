<?php

namespace Tests\Feature\Tarea;

use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * RF-18 (Could): duplicar copia responsable/unidad de la tarea origen; la
 * duplicada es independiente, con su propio historial y su propio creador
 * (quien duplica, no el creador original).
 */
class DuplicarTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function crearTareaOrigen(): Tarea
    {
        $responsable = User::factory()->conNivel(NivelJerarquico::Asistente)->create();

        return Tarea::factory()->create([
            'responsable_id' => $responsable->id,
            'creador_id' => $responsable->id,
            'unidad_organizacional_id' => $responsable->unidad_organizacional_id,
            'titulo' => 'Instalacion electrica Bodega 4',
        ]);
    }

    public function test_duplicar_copia_responsable_y_unidad_pero_permite_editar_titulo_y_fechas(): void
    {
        $origen = $this->crearTareaOrigen();
        $quienDuplica = User::factory()->conNivel(NivelJerarquico::Directorio)->create();

        $response = $this->actingAs($quienDuplica)->post("/tareas/{$origen->id}/duplicar", [
            'titulo' => 'Instalacion electrica Bodega 5',
            'descripcion' => 'Copia para la bodega nueva',
            'fecha_compromiso' => now()->addDays(10)->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $duplicada = Tarea::where('titulo', 'Instalacion electrica Bodega 5')->firstOrFail();
        $this->assertSame($origen->responsable_id, $duplicada->responsable_id);
        $this->assertSame($origen->unidad_organizacional_id, $duplicada->unidad_organizacional_id);
        $this->assertSame('Copia para la bodega nueva', $duplicada->descripcion);
    }

    public function test_la_tarea_duplicada_es_independiente_con_su_propio_historial_y_creador(): void
    {
        $origen = $this->crearTareaOrigen();
        $quienDuplica = User::factory()->conNivel(NivelJerarquico::Directorio)->create();

        $this->actingAs($quienDuplica)->post("/tareas/{$origen->id}/duplicar", [
            'titulo' => 'Duplicada',
            'fecha_compromiso' => now()->addDays(5)->toDateString(),
        ]);

        $duplicada = Tarea::where('titulo', 'Duplicada')->firstOrFail();

        // Creador es quien duplico, no el creador de la tarea origen.
        $this->assertSame($quienDuplica->id, $duplicada->creador_id);
        $this->assertNotEquals($origen->creador_id, $duplicada->creador_id);

        // Historial propio desde cero: solo el evento de creacion de la duplicada.
        $this->assertCount(1, $duplicada->historial);
        $this->assertTrue($duplicada->historial->first()->tipo_evento === TipoEvento::Creacion);
        $this->assertNotSame($origen->id, $duplicada->id);
    }

    public function test_no_se_puede_duplicar_con_fecha_compromiso_pasada(): void
    {
        $origen = $this->crearTareaOrigen();
        $quienDuplica = User::factory()->conNivel(NivelJerarquico::Directorio)->create();

        $response = $this->actingAs($quienDuplica)->post("/tareas/{$origen->id}/duplicar", [
            'titulo' => 'Duplicada invalida',
            'fecha_compromiso' => now()->subDay()->toDateString(),
        ]);

        $response->assertSessionHasErrors('fecha_compromiso');
        $this->assertDatabaseMissing('tareas', ['titulo' => 'Duplicada invalida'], 'usuarios');
    }
}
