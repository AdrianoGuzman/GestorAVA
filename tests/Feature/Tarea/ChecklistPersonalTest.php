<?php

namespace Tests\Feature\Tarea;

use App\Enums\NivelJerarquico;
use App\Models\ChecklistPersonalItem;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class ChecklistPersonalTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_puede_agregar_un_item_a_su_checklist_personal(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/checklist-personal", [
            "texto" => "Revisar el plano antes de arrancar.",
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertDatabaseHas("checklist_personal_items", [
            "tarea_id" => $tarea->id,
            "usuario_id" => $responsable->id,
            "texto" => "Revisar el plano antes de arrancar.",
            "completado" => false,
        ]);
    }

    public function test_un_usuario_ajeno_no_puede_agregar_items(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($ajeno)->post("/tareas/{$tarea->id}/checklist-personal", [
            "texto" => "Intento invalido",
        ])->assertSessionHas("error");

        $this->assertDatabaseCount("checklist_personal_items", 0);
    }

    public function test_el_dueño_puede_alternar_su_propio_item(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $item = ChecklistPersonalItem::factory()->create([
            "tarea_id" => $tarea->id,
            "usuario_id" => $responsable->id,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/checklist-personal/{$item->id}")
            ->assertRedirect();

        $this->assertTrue($item->fresh()->completado);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/checklist-personal/{$item->id}")
            ->assertRedirect();

        $this->assertFalse($item->fresh()->completado);
    }

    public function test_otro_colaborador_no_puede_alternar_el_item_de_otro(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);
        $item = ChecklistPersonalItem::factory()->create([
            "tarea_id" => $tarea->id,
            "usuario_id" => $responsable->id,
        ]);

        $this->actingAs($colaborador)->patch("/tareas/{$tarea->id}/checklist-personal/{$item->id}")
            ->assertSessionHas("error");

        $this->assertFalse($item->fresh()->completado);
    }

    public function test_el_dueño_puede_eliminar_su_propio_item(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $item = ChecklistPersonalItem::factory()->create([
            "tarea_id" => $tarea->id,
            "usuario_id" => $responsable->id,
        ]);

        $this->actingAs($responsable)->delete("/tareas/{$tarea->id}/checklist-personal/{$item->id}")
            ->assertRedirect()->assertSessionHas("success");

        $this->assertDatabaseMissing("checklist_personal_items", ["id" => $item->id]);
    }

    public function test_es_privado_cada_colaborador_solo_ve_los_suyos(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        ChecklistPersonalItem::factory()->create([
            "tarea_id" => $tarea->id,
            "usuario_id" => $responsable->id,
            "texto" => "Item del responsable",
        ]);
        ChecklistPersonalItem::factory()->create([
            "tarea_id" => $tarea->id,
            "usuario_id" => $colaborador->id,
            "texto" => "Item del colaborador",
        ]);

        $response = $this->actingAs($colaborador)->get("/tareas/{$tarea->id}")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("checklistPersonal", 1)
            ->where("checklistPersonal.0.texto", "Item del colaborador"));
    }
}
