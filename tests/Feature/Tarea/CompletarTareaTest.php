<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\ChecklistItem;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\Support\GuardDeBloqueoDePruebas;
use Tests\TestCase;

class CompletarTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_puede_completar_la_tarea(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertRedirect()->assertSessionHas("success");

        $tarea->refresh();
        $this->assertSame(EstadoTarea::Completada, $tarea->estado);
        $this->assertTrue($tarea->historial()->where("tipo_evento", TipoEvento::Completada)->exists());
    }

    public function test_un_colaborador_no_puede_completar_la_tarea(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        $this->actingAs($colaborador)->patch("/tareas/{$tarea->id}/completar")
            ->assertSessionHas("error");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_un_usuario_ajeno_no_puede_completar_la_tarea(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($ajeno)->patch("/tareas/{$tarea->id}/completar")
            ->assertSessionHas("error");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_no_se_puede_completar_una_tarea_ya_completada(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertSessionHasErrors("estado");
    }

    public function test_un_guard_registrado_bloquea_el_completado_con_su_motivo(): void
    {
        config(["tareas.guards_completar" => [GuardDeBloqueoDePruebas::class]]);

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "titulo" => "BLOQUEADA_TEST",
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertSessionHasErrors("bloqueos");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_un_guard_registrado_que_no_bloquea_permite_completar(): void
    {
        config(["tareas.guards_completar" => [GuardDeBloqueoDePruebas::class]]);

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "titulo" => "Tarea normal",
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertRedirect()->assertSessionHas("success");

        $this->assertSame(EstadoTarea::Completada, $tarea->fresh()->estado);
    }

    public function test_no_se_puede_completar_si_hay_items_del_checklist_compartido_sin_marcar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);
        ChecklistItem::factory()->create(["tarea_id" => $tarea->id, "completado" => false]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertSessionHasErrors("bloqueos");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_se_puede_completar_si_todos_los_items_del_checklist_estan_marcados(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);
        ChecklistItem::factory()->create(["tarea_id" => $tarea->id, "completado" => true]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertRedirect()->assertSessionHas("success");

        $this->assertSame(EstadoTarea::Completada, $tarea->fresh()->estado);
    }

    public function test_no_se_puede_completar_si_hay_una_tarea_hija_sin_terminar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $padre = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        Tarea::factory()->hijaDe($padre)->create(["estado" => EstadoTarea::EnProgreso]);

        $this->actingAs($responsable)->patch("/tareas/{$padre->id}/completar")
            ->assertSessionHasErrors("bloqueos");

        $this->assertSame(EstadoTarea::EnProgreso, $padre->fresh()->estado);
    }

    public function test_se_puede_completar_si_todas_las_tareas_hijas_estan_completadas_o_canceladas(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $padre = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        Tarea::factory()->hijaDe($padre)->completada()->create();
        Tarea::factory()->hijaDe($padre)->cancelada()->create();

        $this->actingAs($responsable)->patch("/tareas/{$padre->id}/completar")
            ->assertRedirect()->assertSessionHas("success");

        $this->assertSame(EstadoTarea::Completada, $padre->fresh()->estado);
    }

    public function test_no_se_puede_completar_si_evidencia_obligatoria_y_nadie_subio_un_adjunto_necesario(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
            "evidencia_obligatoria" => true,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertSessionHasErrors("bloqueos");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_se_puede_completar_si_evidencia_obligatoria_y_ya_hay_un_adjunto_necesario(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
            "evidencia_obligatoria" => true,
        ]);
        $tarea->adjuntos()->create([
            "usuario_id" => $responsable->id,
            "nombre_original" => "certificado.pdf",
            "ruta" => "adjuntos/certificado.pdf",
            "mime_type" => "application/pdf",
            "tamano_bytes" => 1024,
            "categoria" => "necesario",
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertRedirect()->assertSessionHas("success");

        $this->assertSame(EstadoTarea::Completada, $tarea->fresh()->estado);
    }

    public function test_un_adjunto_de_evidencia_no_sirve_para_cumplir_la_evidencia_obligatoria(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
            "evidencia_obligatoria" => true,
        ]);
        $tarea->adjuntos()->create([
            "usuario_id" => $responsable->id,
            "nombre_original" => "foto.jpg",
            "ruta" => "adjuntos/foto.jpg",
            "mime_type" => "image/jpeg",
            "tamano_bytes" => 2048,
            "categoria" => "evidencia",
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/completar")
            ->assertSessionHasErrors("bloqueos");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }
}
