<?php

namespace Tests\Feature\Proyecto;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * Secciones dentro de un Proyecto (14-09-2026): agrupan tareas por objetivo,
 * con un peso que pondera el avance del proyecto completo.
 */
class SeccionTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_directorio_puede_crear_una_seccion(): void
    {
        $directorio = $this->usuario(NivelJerarquico::Directorio);
        $proyecto = Proyecto::factory()->create();

        $response = $this->actingAs($directorio)->post("/proyectos/{$proyecto->id}/secciones", [
            "nombre" => "Objetivo 1: Cultura",
            "peso" => 0.6,
        ]);

        $response->assertSessionHas("success");
        $this->assertDatabaseHas("secciones", ["proyecto_id" => $proyecto->id, "nombre" => "Objetivo 1: Cultura"], "usuarios");
    }

    public function test_un_jefe_de_area_no_puede_crear_una_seccion_aunque_conozca_la_ruta(): void
    {
        $jefeArea = $this->usuario(NivelJerarquico::JefeArea);
        $proyecto = Proyecto::factory()->create();

        $response = $this->actingAs($jefeArea)->post("/proyectos/{$proyecto->id}/secciones", [
            "nombre" => "Intento invalido",
            "peso" => 0.5,
        ]);

        $response->assertSessionHas("error");
        $this->assertDatabaseMissing("secciones", ["nombre" => "Intento invalido"], "usuarios");
    }

    public function test_directorio_puede_editar_una_seccion(): void
    {
        $directorio = $this->usuario(NivelJerarquico::Directorio);
        $seccion = Seccion::factory()->create(["nombre" => "Nombre viejo", "peso" => 0.3]);

        $response = $this->actingAs($directorio)->patch("/secciones/{$seccion->id}", [
            "nombre" => "Nombre nuevo",
            "peso" => 0.7,
        ]);

        $response->assertSessionHas("success");
        $seccion->refresh();
        $this->assertSame("Nombre nuevo", $seccion->nombre);
        $this->assertEqualsWithDelta(0.7, $seccion->peso, 0.001);
    }

    public function test_rechaza_un_peso_fuera_de_rango(): void
    {
        $directorio = $this->usuario(NivelJerarquico::Directorio);
        $proyecto = Proyecto::factory()->create();

        $this->actingAs($directorio)->post("/proyectos/{$proyecto->id}/secciones", [
            "nombre" => "Peso invalido",
            "peso" => 1.5,
        ])->assertSessionHasErrors("peso");
    }

    /**
     * Franco (14-09-2026): el avance del proyecto es el promedio ponderado
     * del avance de cada seccion; las tareas sin seccion no participan.
     */
    public function test_el_avance_del_proyecto_pondera_por_seccion_e_ignora_tareas_sin_seccion(): void
    {
        $directorio = $this->usuario(NivelJerarquico::Directorio);
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $proyecto = Proyecto::factory()->create();

        // Seccion A: peso 0.6, 1 de 2 tareas completada -> avance 0.5
        $seccionA = Seccion::factory()->create(["proyecto_id" => $proyecto->id, "peso" => 0.6]);
        Tarea::factory()->create([
            "responsable_id" => $responsable->id, "unidad_organizacional_id" => $obra->id,
            "proyecto_id" => $proyecto->id, "seccion_id" => $seccionA->id, "estado" => EstadoTarea::Completada,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $responsable->id, "unidad_organizacional_id" => $obra->id,
            "proyecto_id" => $proyecto->id, "seccion_id" => $seccionA->id, "estado" => EstadoTarea::Pendiente,
        ]);

        // Seccion B: peso 0.4, ambas completadas -> avance 1.0
        $seccionB = Seccion::factory()->create(["proyecto_id" => $proyecto->id, "peso" => 0.4]);
        Tarea::factory()->count(2)->create([
            "responsable_id" => $responsable->id, "unidad_organizacional_id" => $obra->id,
            "proyecto_id" => $proyecto->id, "seccion_id" => $seccionB->id, "estado" => EstadoTarea::Completada,
        ]);

        // Tarea suelta sin seccion, pendiente -- no deberia afectar el promedio.
        Tarea::factory()->create([
            "responsable_id" => $responsable->id, "unidad_organizacional_id" => $obra->id,
            "proyecto_id" => $proyecto->id, "seccion_id" => null, "estado" => EstadoTarea::Pendiente,
        ]);

        $response = $this->actingAs($directorio)->get("/mis-proyectos");

        $response->assertOk();
        // (0.6*0.5 + 0.4*1.0) / (0.6+0.4) = 0.7
        $response->assertInertia(fn ($page) => $page->where("proyectos.0.avance", 0.7));

        $this->actingAs($directorio)->get("/proyectos/{$proyecto->id}")
            ->assertInertia(fn ($page) => $page
                ->where("proyecto.avance", 0.7)
                ->has("proyecto.tareasSinSeccion", 1));
    }

    public function test_sin_secciones_el_avance_cae_al_calculo_simple_de_todas_las_tareas(): void
    {
        $directorio = $this->usuario(NivelJerarquico::Directorio);
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $proyecto = Proyecto::factory()->create();

        Tarea::factory()->create([
            "responsable_id" => $responsable->id, "unidad_organizacional_id" => $obra->id,
            "proyecto_id" => $proyecto->id, "estado" => EstadoTarea::Completada,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $responsable->id, "unidad_organizacional_id" => $obra->id,
            "proyecto_id" => $proyecto->id, "estado" => EstadoTarea::Pendiente,
        ]);

        $response = $this->actingAs($directorio)->get("/mis-proyectos");

        $response->assertInertia(fn ($page) => $page->where("proyectos.0.avance", 0.5));
    }
}
