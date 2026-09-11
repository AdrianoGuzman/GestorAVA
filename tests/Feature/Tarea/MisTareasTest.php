<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class MisTareasTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_sin_tareas_relacionadas_todas_las_secciones_quedan_vacias(): void
    {
        $usuario = $this->usuario(NivelJerarquico::Asistente);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("secciones.responsable.contadores.total", 0)
            ->where("secciones.colaborador.contadores.total", 0)
            ->where("secciones.delegadas_por_mi.contadores.total", 0)
            ->where("secciones.creadas_por_mi.contadores.total", 0)
            ->has("secciones.responsable.tareas", 0)
            ->has("secciones.colaborador.tareas", 0)
            ->has("secciones.delegadas_por_mi.tareas", 0)
            ->has("secciones.creadas_por_mi.tareas", 0));
    }

    public function test_una_tarea_donde_es_responsable_aparece_en_esa_seccion(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("secciones.responsable.contadores.total", 1)
            ->where("secciones.responsable.contadores.en_progreso", 1)
            ->where("secciones.responsable.tareas.0.id", $tarea->id)
            ->has("secciones.colaborador.tareas", 0));
    }

    public function test_una_tarea_donde_es_colaborador_aparece_en_esa_seccion(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $otro = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $otro->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($usuario->id);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("secciones.colaborador.contadores.total", 1)
            ->where("secciones.colaborador.tareas.0.id", $tarea->id));
    }

    public function test_una_tarea_que_reasigno_aparece_en_delegadas_por_mi_aunque_ya_no_participe(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $otro = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $otro->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->historial()->create([
            "tipo_evento" => TipoEvento::Reasignacion,
            "usuario_id" => $usuario->id,
            "datos_evento" => ["responsable_nuevo_id" => $otro->id],
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("secciones.delegadas_por_mi.contadores.total", 1)
            ->where("secciones.delegadas_por_mi.tareas.0.id", $tarea->id)
            ->has("secciones.responsable.tareas", 0)
            ->has("secciones.colaborador.tareas", 0)
            ->has("secciones.creadas_por_mi.tareas", 0));
    }

    public function test_creada_y_asignada_a_otro_sin_reasignacion_aparece_solo_en_creadas_por_mi(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $creador = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $otro = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "creador_id" => $creador->id,
            "responsable_id" => $otro->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $response = $this->actingAs($creador)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("secciones.creadas_por_mi.contadores.total", 1)
            ->where("secciones.creadas_por_mi.tareas.0.id", $tarea->id)
            ->has("secciones.delegadas_por_mi.tareas", 0));
    }

    public function test_creada_y_responsable_no_se_duplica_en_creadas_por_mi(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "creador_id" => $usuario->id,
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("secciones.responsable.contadores.total", 1)
            ->has("secciones.creadas_por_mi.tareas", 0));
    }

    public function test_contadores_agregados_por_seccion(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
            "esta_atrasada" => true,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("secciones.responsable.contadores.total", 3)
            ->where("secciones.responsable.contadores.atrasadas", 1)
            ->where("secciones.responsable.contadores.en_progreso", 1)
            ->where("secciones.responsable.contadores.completadas", 1));
    }

    public function test_filtro_rol_devuelve_solo_esa_seccion(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas?filtro_rol=responsable")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("secciones.responsable")
            ->missing("secciones.colaborador")
            ->missing("secciones.delegadas_por_mi")
            ->missing("secciones.creadas_por_mi"));
    }

    public function test_filtro_rol_invalido_es_rechazado(): void
    {
        $usuario = $this->usuario(NivelJerarquico::Asistente);

        $this->actingAs($usuario)->get("/mis-tareas?filtro_rol=inexistente")
            ->assertSessionHasErrors("filtro_rol");
    }
}
