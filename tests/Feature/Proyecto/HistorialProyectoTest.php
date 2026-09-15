<?php

namespace Tests\Feature\Proyecto;

use App\Enums\EstadoProyecto;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEventoProyecto;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * Trazabilidad del Proyecto (14-09-2026, pedida por Franco): quien lo creó,
 * qué cambió en cada edición, y cuándo se agrega/edita una Sección -- mismo
 * criterio de diff que ya tiene Tarea ("Idea A", 13-09-2026).
 */
class HistorialProyectoTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function directorio(): User
    {
        return User::factory()->conNivel(NivelJerarquico::Directorio)->create();
    }

    public function test_crear_un_proyecto_registra_el_evento_de_creacion(): void
    {
        $directorio = $this->directorio();

        $this->actingAs($directorio)->post('/proyectos', [
            'nombre' => 'Cultura preventiva',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => now()->addMonths(6)->toDateString(),
        ]);

        $proyecto = Proyecto::where('nombre', 'Cultura preventiva')->firstOrFail();
        $evento = $proyecto->historial()->where('tipo_evento', TipoEventoProyecto::Creacion)->first();

        $this->assertNotNull($evento);
        $this->assertSame($directorio->id, $evento->usuario_id);
        $this->assertSame('Cultura preventiva', $evento->datos_evento['nombre']);
    }

    public function test_editar_un_proyecto_registra_el_diff_de_lo_que_cambio(): void
    {
        $directorio = $this->directorio();
        $proyecto = Proyecto::factory()->create(['nombre' => 'Nombre viejo', 'estado' => EstadoProyecto::Activo]);

        $this->actingAs($directorio)->patch("/proyectos/{$proyecto->id}", [
            'nombre' => 'Nombre nuevo',
            'estado' => EstadoProyecto::Cerrado->value,
            'fecha_inicio' => $proyecto->fecha_inicio->toDateString(),
            'fecha_termino' => $proyecto->fecha_termino->toDateString(),
        ]);

        $evento = $proyecto->historial()->where('tipo_evento', TipoEventoProyecto::ProyectoEditado)->firstOrFail();

        $this->assertSame('Nombre viejo', $evento->datos_evento['datos_anteriores']['nombre']);
        $this->assertSame('Nombre nuevo', $evento->datos_evento['nombre']);
        $this->assertSame('activo', $evento->datos_evento['datos_anteriores']['estado']);
        $this->assertSame('cerrado', $evento->datos_evento['estado']);
    }

    public function test_crear_una_seccion_registra_el_evento(): void
    {
        $directorio = $this->directorio();
        $proyecto = Proyecto::factory()->create();

        $this->actingAs($directorio)->post("/proyectos/{$proyecto->id}/secciones", [
            'nombre' => 'Objetivo 1: Cultura',
            'peso' => 0.6,
        ]);

        $evento = $proyecto->historial()->where('tipo_evento', TipoEventoProyecto::SeccionCreada)->firstOrFail();

        $this->assertSame('Objetivo 1: Cultura', $evento->datos_evento['seccion_nombre']);
        $this->assertEqualsWithDelta(0.6, $evento->datos_evento['peso'], 0.001);
    }

    public function test_editar_una_seccion_registra_el_diff(): void
    {
        $directorio = $this->directorio();
        $proyecto = Proyecto::factory()->create();
        $seccion = Seccion::factory()->create(['proyecto_id' => $proyecto->id, 'nombre' => 'Nombre viejo', 'peso' => 0.3]);

        $this->actingAs($directorio)->patch("/secciones/{$seccion->id}", [
            'nombre' => 'Nombre nuevo',
            'peso' => 0.7,
        ]);

        $evento = $proyecto->historial()->where('tipo_evento', TipoEventoProyecto::SeccionEditada)->firstOrFail();

        $this->assertSame('Nombre viejo', $evento->datos_evento['datos_anteriores']['nombre']);
        $this->assertSame('Nombre nuevo', $evento->datos_evento['nombre']);
    }

    public function test_el_detalle_del_proyecto_trae_su_historial(): void
    {
        $directorio = $this->directorio();

        $this->actingAs($directorio)->post('/proyectos', [
            'nombre' => 'Proyecto con historial',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => now()->addMonth()->toDateString(),
        ]);
        $proyecto = Proyecto::where('nombre', 'Proyecto con historial')->firstOrFail();

        $response = $this->actingAs($directorio)->get("/proyectos/{$proyecto->id}");

        $response->assertInertia(fn ($page) => $page->has('proyecto.historial', 1));
    }
}
