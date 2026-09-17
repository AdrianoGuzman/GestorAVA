<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Proyecto;
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

    public function test_sin_tareas_relacionadas_la_lista_queda_vacia(): void
    {
        $usuario = $this->usuario(NivelJerarquico::Asistente);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("contadores.total", 0)
            ->has("tareas", 0));
    }

    public function test_una_tarea_donde_es_responsable_aparece_con_ese_rol(): void
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
            ->has("tareas", 1)
            ->where("tareas.0.id", $tarea->id)
            ->where("tareas.0.rol", "responsable")
            ->where("contadores.en_progreso", 1));
    }

    public function test_una_tarea_donde_es_colaborador_aparece_con_ese_rol(): void
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
            ->has("tareas", 1)
            ->where("tareas.0.id", $tarea->id)
            ->where("tareas.0.rol", "colaborador"));
    }

    public function test_una_tarea_que_reasigno_aparece_como_delegada_aunque_ya_no_participe(): void
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
            ->has("tareas", 1)
            ->where("tareas.0.id", $tarea->id)
            ->where("tareas.0.rol", "delegado"));
    }

    public function test_creada_y_asignada_a_otro_sin_reasignacion_aparece_como_creador(): void
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
            ->has("tareas", 1)
            ->where("tareas.0.id", $tarea->id)
            ->where("tareas.0.rol", "creador"));
    }

    public function test_creada_y_responsable_no_se_duplica(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        Tarea::factory()->create([
            "creador_id" => $usuario->id,
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.rol", "responsable"));
    }

    public function test_delegada_y_colaborador_no_se_duplica(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $nuevoResponsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($usuario->id);
        $tarea->historial()->create([
            "tipo_evento" => TipoEvento::Reasignacion,
            "usuario_id" => $usuario->id,
            "datos_evento" => ["responsable_nuevo_id" => $nuevoResponsable->id],
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $tarea->id)
            ->where("tareas.0.rol", "colaborador"));
    }

    public function test_contadores_agregados(): void
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

        // Sin esto, "completada" queda fuera por el default de estados
        // activos y los contadores no reflejarian las 3 tareas creadas.
        $response = $this->actingAs($usuario)
            ->get("/mis-tareas?estado[]=pendiente&estado[]=en_progreso&estado[]=completada")
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("contadores.total", 3)
            ->where("contadores.atrasadas", 1)
            ->where("contadores.en_progreso", 1)
            ->where("contadores.pendientes", 1)
            ->where("contadores.completadas", 1));
    }

    public function test_sin_filtro_de_estado_se_ocultan_completadas_y_canceladas_por_defecto(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $pendiente = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Cancelada,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $pendiente->id)
            ->where("filtros.estado", ["pendiente", "en_progreso"]));
    }

    public function test_filtro_de_estado_explicito_puede_traer_completadas(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $completada = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas?estado[]=completada")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $completada->id));
    }

    public function test_filtro_rol_devuelve_solo_ese_rol(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $otro = $this->usuario(NivelJerarquico::Asistente, $obra);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tareaColaborador = Tarea::factory()->create([
            "responsable_id" => $otro->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tareaColaborador->colaboradores()->attach($usuario->id);

        $response = $this->actingAs($usuario)->get("/mis-tareas?filtro_rol=colaborador")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.rol", "colaborador"));
    }

    public function test_filtro_rol_invalido_es_rechazado(): void
    {
        $usuario = $this->usuario(NivelJerarquico::Asistente);

        $this->actingAs($usuario)->get("/mis-tareas?filtro_rol=inexistente")
            ->assertSessionHasErrors("filtro_rol");
    }

    public function test_busqueda_por_nombre_filtra_la_lista(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "titulo" => "Revisar tablero electrico",
        ]);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "titulo" => "Coordinar visita a terreno",
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas?busqueda=tablero")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $tarea->id));
    }

    public function test_busqueda_por_codigo_encuentra_la_tarea(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas?busqueda={$tarea->codigo}")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $tarea->id));
    }

    public function test_filtro_estado_filtra_la_lista(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $pendiente = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas?estado[]=pendiente")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $pendiente->id));
    }

    public function test_filtro_solo_atrasadas(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $atrasada = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "esta_atrasada" => true,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
            "esta_atrasada" => false,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas?solo_atrasadas=1")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $atrasada->id));
    }

    public function test_el_ultimo_evento_del_historial_viaja_con_cada_tarea(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        $otro = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->historial()->create([
            "tipo_evento" => TipoEvento::TareaEditada,
            "usuario_id" => $otro->id,
            "datos_evento" => [],
        ])->forceFill(["created_at" => now()->subDay()])->save();
        $tarea->historial()->create([
            "tipo_evento" => TipoEvento::Reasignacion,
            "usuario_id" => $usuario->id,
            "datos_evento" => [],
        ])->forceFill(["created_at" => now()])->save();

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->where("tareas.0.ultimo_evento.tipo_evento", "reasignacion")
            ->where("tareas.0.ultimo_evento.usuario.id", $usuario->id)
            ->where("tareas.0.id", $tarea->id));
    }

    public function test_una_tarea_sin_historial_no_trae_ultimo_evento(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obra);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas")->assertOk();

        $response->assertInertia(fn ($page) => $page->where("tareas.0.ultimo_evento", null));
    }

    public function test_filtro_unidad_organizacional(): void
    {
        $obraA = UnidadOrganizacional::factory()->create();
        $obraB = UnidadOrganizacional::factory()->create();
        $usuario = $this->usuario(NivelJerarquico::Asistente, $obraA);
        $tareaA = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obraA->id,
        ]);
        Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "unidad_organizacional_id" => $obraB->id,
        ]);

        $response = $this->actingAs($usuario)->get("/mis-tareas?unidad_organizacional_id={$obraA->id}")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $tareaA->id));
    }

    public function test_filtro_proyecto(): void
    {
        $usuario = $this->usuario(NivelJerarquico::Asistente);
        $proyecto = Proyecto::factory()->create();
        $tareaDelProyecto = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
            "proyecto_id" => $proyecto->id,
        ]);
        Tarea::factory()->create(["responsable_id" => $usuario->id, "proyecto_id" => null]);

        $response = $this->actingAs($usuario)->get("/mis-tareas?proyecto_id={$proyecto->id}")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("tareas", 1)
            ->where("tareas.0.id", $tareaDelProyecto->id));
    }
}
