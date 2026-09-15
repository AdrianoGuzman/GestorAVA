<?php

namespace Tests\Feature\Proyecto;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEventoProyecto;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Services\ExportacionProyectoService;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * Exportar el registro completo de un Proyecto a PDF y Excel (14-09-2026,
 * Franco): mismo criterio que ExportarTareaTest -- datos generales,
 * secciones, tareas, e historial con el mismo nivel de detalle que el
 * timeline interactivo.
 */
class ExportarProyectoTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function tareaConContenido(): Proyecto
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = User::factory()->conNivel(NivelJerarquico::Asistente, $obra)->create();
        $proyecto = Proyecto::factory()->create();
        $seccion = Seccion::factory()->create(['proyecto_id' => $proyecto->id, 'peso' => 0.6]);
        Tarea::factory()->create([
            'responsable_id' => $responsable->id,
            'unidad_organizacional_id' => $obra->id,
            'proyecto_id' => $proyecto->id,
            'seccion_id' => $seccion->id,
            'estado' => EstadoTarea::Completada,
        ]);
        Tarea::factory()->create([
            'responsable_id' => $responsable->id,
            'unidad_organizacional_id' => $obra->id,
            'proyecto_id' => $proyecto->id,
            'seccion_id' => null,
        ]);

        return $proyecto;
    }

    public function test_se_puede_exportar_un_proyecto_a_pdf(): void
    {
        $proyecto = $this->tareaConContenido();
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get("/proyectos/{$proyecto->id}/exportar-pdf");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_se_puede_exportar_un_proyecto_a_excel(): void
    {
        $proyecto = $this->tareaConContenido();
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->get("/proyectos/{$proyecto->id}/exportar-excel");

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_un_proyecto_sin_secciones_ni_tareas_tambien_se_exporta(): void
    {
        $proyecto = Proyecto::factory()->create();
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->get("/proyectos/{$proyecto->id}/exportar-pdf")->assertOk();
        $this->actingAs($usuario)->get("/proyectos/{$proyecto->id}/exportar-excel")->assertOk();
    }

    public function test_las_tareas_exportadas_incluyen_su_seccion_y_las_sin_seccion(): void
    {
        $proyecto = $this->tareaConContenido();

        $filas = app(ExportacionProyectoService::class)->tareas($proyecto->load([
            'secciones.tareas.responsable',
            'tareas' => fn ($query) => $query->with('responsable')->whereNull('seccion_id'),
        ]));

        $this->assertCount(2, $filas);
        $this->assertContains('Sin sección', array_column($filas, 'sección'));
    }

    public function test_el_historial_exportado_detalla_la_creacion_de_una_seccion(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $proyecto = Proyecto::factory()->create();

        $this->actingAs($directorio)->post("/proyectos/{$proyecto->id}/secciones", [
            'nombre' => 'Objetivo 1: Cultura',
            'peso' => 0.6,
        ]);

        $proyecto->load(['historial' => fn ($query) => $query->with('usuario'), 'secciones.tareas', 'tareas']);
        $filas = app(ExportacionProyectoService::class)->historial($proyecto);

        $this->assertSame('Sección: Objetivo 1: Cultura (peso 60%)', $filas[0]['detalle'][0]);
    }

    public function test_el_historial_exportado_detalla_el_diff_de_editar_el_proyecto(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $proyecto = Proyecto::factory()->create(['nombre' => 'Nombre viejo']);

        $this->actingAs($directorio)->patch("/proyectos/{$proyecto->id}", [
            'nombre' => 'Nombre nuevo',
            'estado' => 'activo',
            'fecha_inicio' => $proyecto->fecha_inicio->toDateString(),
            'fecha_termino' => $proyecto->fecha_termino->toDateString(),
        ]);

        $proyecto->load(['historial' => fn ($query) => $query->with('usuario'), 'secciones.tareas', 'tareas']);
        $filas = app(ExportacionProyectoService::class)->historial($proyecto);
        $evento = collect($filas)->firstWhere('evento', TipoEventoProyecto::ProyectoEditado->label());

        $this->assertSame('Nombre: Nombre viejo → Nombre nuevo', $evento['detalle'][0]);
    }
}
