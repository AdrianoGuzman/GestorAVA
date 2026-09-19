<?php

namespace Tests\Feature\Proyecto;

use App\Enums\NivelJerarquico;
use App\Models\Proyecto;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * Aplazar la entrega de un Proyecto (AVA Montajes, reunión 15-09-2026):
 * mismo permiso que administrar el proyecto, motivo obligatorio, y solo
 * corre la fecha hacia adelante.
 */
class AplazarEntregaProyectoTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_directorio_puede_aplazar_la_entrega_con_motivo(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $proyecto = Proyecto::factory()->create([
            'fecha_termino' => now()->addMonth()->toDateString(),
        ]);
        $nuevaFecha = now()->addMonths(2)->toDateString();

        $response = $this->actingAs($directorio)->patch("/proyectos/{$proyecto->id}/aplazar-entrega", [
            'fecha_termino' => $nuevaFecha,
            'motivo' => 'Falta de material eléctrico en bodega.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $proyecto->refresh();
        $this->assertSame($nuevaFecha, $proyecto->fecha_termino->toDateString());

        $evento = $proyecto->historial()->latest('id')->first();
        $this->assertSame('entrega_aplazada', $evento->tipo_evento->value);
        $this->assertSame('Falta de material eléctrico en bodega.', $evento->datos_evento['motivo']);
        $this->assertSame($nuevaFecha, $evento->datos_evento['fecha_termino']);
    }

    public function test_gerencia_puede_aplazar_la_entrega(): void
    {
        $gerencia = User::factory()->conNivel(NivelJerarquico::Gerencia)->create();
        $proyecto = Proyecto::factory()->create(['fecha_termino' => now()->addMonth()->toDateString()]);

        $response = $this->actingAs($gerencia)->patch("/proyectos/{$proyecto->id}/aplazar-entrega", [
            'fecha_termino' => now()->addMonths(2)->toDateString(),
            'motivo' => 'Se suma una capacitación adicional.',
        ]);

        $response->assertSessionHas('success');
    }

    public function test_un_jefe_de_area_no_puede_aplazar_la_entrega_aunque_conozca_la_ruta(): void
    {
        $jefeArea = User::factory()->conNivel(NivelJerarquico::JefeArea)->create();
        $proyecto = Proyecto::factory()->create(['fecha_termino' => now()->addMonth()->toDateString()]);
        $fechaOriginal = $proyecto->fecha_termino->toDateString();

        $response = $this->actingAs($jefeArea)->patch("/proyectos/{$proyecto->id}/aplazar-entrega", [
            'fecha_termino' => now()->addMonths(2)->toDateString(),
            'motivo' => 'Intento inválido.',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame($fechaOriginal, $proyecto->fresh()->fecha_termino->toDateString());
    }

    public function test_el_motivo_es_obligatorio(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $proyecto = Proyecto::factory()->create(['fecha_termino' => now()->addMonth()->toDateString()]);

        $this->actingAs($directorio)->patch("/proyectos/{$proyecto->id}/aplazar-entrega", [
            'fecha_termino' => now()->addMonths(2)->toDateString(),
        ])->assertSessionHasErrors('motivo');
    }

    public function test_no_se_puede_aplazar_a_una_fecha_anterior_o_igual_a_la_actual(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $proyecto = Proyecto::factory()->create(['fecha_termino' => now()->addMonth()->toDateString()]);

        $this->actingAs($directorio)->patch("/proyectos/{$proyecto->id}/aplazar-entrega", [
            'fecha_termino' => $proyecto->fecha_termino->toDateString(),
            'motivo' => 'Misma fecha, no debería pasar.',
        ])->assertSessionHasErrors('fecha_termino');

        $this->actingAs($directorio)->patch("/proyectos/{$proyecto->id}/aplazar-entrega", [
            'fecha_termino' => now()->toDateString(),
            'motivo' => 'Fecha anterior, no debería pasar.',
        ])->assertSessionHasErrors('fecha_termino');
    }
}
