<?php

namespace Tests\Feature\Proyecto;

use App\Enums\EstadoProyecto;
use App\Enums\NivelJerarquico;
use App\Models\Proyecto;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * "Mis proyectos" (14-09-2026): agrupa tareas de varias unidades bajo una
 * misma iniciativa. Ver la nota fechada en ContextoProgramacion.md.
 */
class ProyectoTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_cualquier_usuario_autenticado_puede_ver_mis_proyectos(): void
    {
        $asistente = User::factory()->conNivel(NivelJerarquico::Asistente)->create();

        $this->actingAs($asistente)->get('/mis-proyectos')->assertOk();
    }

    public function test_directorio_puede_crear_un_proyecto(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();

        $response = $this->actingAs($directorio)->post('/proyectos', [
            'nombre' => 'Cultura preventiva',
            'descripcion' => 'Consultoría estratégica con ACHS a través de Programa Dekra.',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => now()->addMonths(6)->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $proyecto = Proyecto::where('nombre', 'Cultura preventiva')->firstOrFail();
        $this->assertSame($directorio->id, $proyecto->creador_id);
        $this->assertSame(EstadoProyecto::Activo, $proyecto->estado);
    }

    public function test_gerencia_puede_crear_un_proyecto(): void
    {
        $gerencia = User::factory()->conNivel(NivelJerarquico::Gerencia)->create();

        $response = $this->actingAs($gerencia)->post('/proyectos', [
            'nombre' => 'Digitalización de obra',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => now()->addMonths(3)->toDateString(),
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('proyectos', ['nombre' => 'Digitalización de obra'], 'usuarios');
    }

    public function test_un_jefe_de_area_no_puede_crear_un_proyecto_aunque_conozca_la_ruta(): void
    {
        $jefeArea = User::factory()->conNivel(NivelJerarquico::JefeArea)->create();

        $response = $this->actingAs($jefeArea)->post('/proyectos', [
            'nombre' => 'Intento invalido',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => now()->addMonth()->toDateString(),
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('proyectos', ['nombre' => 'Intento invalido'], 'usuarios');
    }

    public function test_directorio_puede_editar_y_cerrar_un_proyecto(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $proyecto = Proyecto::factory()->create(['creador_id' => $directorio->id]);

        $response = $this->actingAs($directorio)->patch("/proyectos/{$proyecto->id}", [
            'nombre' => $proyecto->nombre,
            'descripcion' => 'Descripción actualizada',
            'estado' => EstadoProyecto::Cerrado->value,
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => now()->addMonths(2)->toDateString(),
        ]);

        $response->assertSessionHas('success');

        $proyecto->refresh();
        $this->assertSame(EstadoProyecto::Cerrado, $proyecto->estado);
        $this->assertSame('Descripción actualizada', $proyecto->descripcion);
    }

    public function test_un_jefe_de_area_no_puede_editar_un_proyecto_aunque_conozca_la_ruta(): void
    {
        $jefeArea = User::factory()->conNivel(NivelJerarquico::JefeArea)->create();
        $proyecto = Proyecto::factory()->create();

        $response = $this->actingAs($jefeArea)->patch("/proyectos/{$proyecto->id}", [
            'nombre' => 'Nombre cambiado',
            'estado' => EstadoProyecto::Cerrado->value,
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => now()->addMonth()->toDateString(),
        ]);

        $response->assertSessionHas('error');

        $proyecto->refresh();
        $this->assertNotSame('Nombre cambiado', $proyecto->nombre);
        $this->assertSame(EstadoProyecto::Activo, $proyecto->estado);
    }

    public function test_no_se_puede_repetir_el_nombre_de_un_proyecto(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        Proyecto::factory()->create(['nombre' => 'Cultura preventiva']);

        $response = $this->actingAs($directorio)->post('/proyectos', [
            'nombre' => 'Cultura preventiva',
            'fecha_inicio' => now()->toDateString(),
            'fecha_termino' => now()->addMonth()->toDateString(),
        ]);

        $response->assertSessionHasErrors('nombre');
    }

    public function test_las_fechas_de_inicio_y_termino_son_obligatorias(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();

        $response = $this->actingAs($directorio)->post('/proyectos', ['nombre' => 'Proyecto sin fechas']);

        $response->assertSessionHasErrors(['fecha_inicio', 'fecha_termino']);
        $this->assertDatabaseMissing('proyectos', ['nombre' => 'Proyecto sin fechas'], 'usuarios');
    }

    public function test_la_fecha_de_termino_no_puede_ser_anterior_a_la_de_inicio(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();

        $response = $this->actingAs($directorio)->post('/proyectos', [
            'nombre' => 'Proyecto con fechas invertidas',
            'fecha_inicio' => now()->addMonth()->toDateString(),
            'fecha_termino' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('fecha_termino');
    }

    public function test_una_tarea_puede_asociarse_a_un_proyecto_y_aparece_en_el_listado(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = User::factory()->conNivel(NivelJerarquico::Asistente, $obra)->create();
        $proyecto = Proyecto::factory()->create();
        Tarea::factory()->create([
            'responsable_id' => $responsable->id,
            'unidad_organizacional_id' => $obra->id,
            'proyecto_id' => $proyecto->id,
        ]);

        $response = $this->actingAs($directorio)->get('/mis-proyectos');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('proyectos.0.id', $proyecto->id));
    }

    public function test_el_detalle_del_proyecto_agrupa_la_tarea_sin_seccion(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = User::factory()->conNivel(NivelJerarquico::Asistente, $obra)->create();
        $proyecto = Proyecto::factory()->create();
        Tarea::factory()->create([
            'responsable_id' => $responsable->id,
            'unidad_organizacional_id' => $obra->id,
            'proyecto_id' => $proyecto->id,
        ]);

        $response = $this->actingAs($directorio)->get("/proyectos/{$proyecto->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('proyecto.id', $proyecto->id)
            ->has('proyecto.tareasSinSeccion', 1));
    }

    public function test_el_detalle_del_proyecto_trae_contadores_de_sus_tareas(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = User::factory()->conNivel(NivelJerarquico::Asistente, $obra)->create();
        $proyecto = Proyecto::factory()->create();
        $seccion = \App\Models\Seccion::factory()->create(['proyecto_id' => $proyecto->id]);

        Tarea::factory()->create([
            'responsable_id' => $responsable->id, 'unidad_organizacional_id' => $obra->id,
            'proyecto_id' => $proyecto->id, 'seccion_id' => $seccion->id, 'estado' => \App\Enums\EstadoTarea::Completada,
        ]);
        Tarea::factory()->create([
            'responsable_id' => $responsable->id, 'unidad_organizacional_id' => $obra->id,
            'proyecto_id' => $proyecto->id, 'seccion_id' => null, 'estado' => \App\Enums\EstadoTarea::Pendiente,
        ]);

        $response = $this->actingAs($directorio)->get("/proyectos/{$proyecto->id}");

        $response->assertInertia(fn ($page) => $page
            ->where('proyecto.contadores.total', 2)
            ->where('proyecto.contadores.completadas', 1)
            ->where('proyecto.contadores.pendientes', 1));
    }
}
