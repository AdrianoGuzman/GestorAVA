<?php

namespace Tests\Feature\Checklist;

use App\Enums\TipoEvento;
use App\Models\ChecklistItem;
use App\Models\Tarea;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class ChecklistTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_crea_un_item_de_checklist_y_registra_el_historial(): void
    {
        $usuario = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
        ]);

        $response = $this->actingAs($usuario)->post("/tareas/{$tarea->id}/checklist", [
            "texto" => "Revisar instalación eléctrica",
        ]);

        $response->assertRedirect();
        $response->assertSessionHas("success");

        $item = ChecklistItem::firstOrFail();

        $this->assertSame($tarea->id, $item->tarea_id);
        $this->assertSame("Revisar instalación eléctrica", $item->texto);
        $this->assertFalse($item->completado);
        $this->assertNull($item->dueno_id);

        $this->assertTrue(
            $tarea->historial()
                ->where("tipo_evento", TipoEvento::ChecklistItemCreado)
                ->exists()
        );
    }

    public function test_marca_y_desmarca_un_item_registrando_el_historial(): void
    {
        $usuario = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
        ]);
        $item = ChecklistItem::factory()->create([
            "tarea_id" => $tarea->id,
        ]);

        $this->actingAs($usuario)
            ->patch("/checklist/{$item->id}/marcar")
            ->assertRedirect();

        $this->assertTrue($item->fresh()->completado);
        $this->assertTrue(
            $tarea->historial()
                ->where("tipo_evento", TipoEvento::ChecklistItemMarcado)
                ->exists()
        );

        $this->actingAs($usuario)
            ->patch("/checklist/{$item->id}/desmarcar")
            ->assertRedirect();

        $this->assertFalse($item->fresh()->completado);
        $this->assertTrue(
            $tarea->historial()
                ->where("tipo_evento", TipoEvento::ChecklistItemDesmarcado)
                ->exists()
        );
    }

    public function test_edita_un_item_y_registra_el_historial(): void
    {
        $usuario = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
        ]);
        $item = ChecklistItem::factory()->create([
            "tarea_id" => $tarea->id,
            "texto" => "Texto anterior",
        ]);

        $this->actingAs($usuario)
            ->patch("/checklist/{$item->id}", [
                "texto" => "Texto actualizado",
            ])
            ->assertRedirect();

        $item->refresh();

        $this->assertSame("Texto actualizado", $item->texto);
        $this->assertTrue(
            $tarea->historial()
                ->where("tipo_evento", TipoEvento::ChecklistItemEditado)
                ->exists()
        );
    }

    public function test_elimina_un_item_y_registra_el_historial(): void
    {
        $usuario = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
        ]);
        $item = ChecklistItem::factory()->create([
            "tarea_id" => $tarea->id,
        ]);

        $this->actingAs($usuario)
            ->delete("/checklist/{$item->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing("checklist_items", [
            "id" => $item->id,
        ]);

        $this->assertTrue(
            $tarea->historial()
                ->where("tipo_evento", TipoEvento::ChecklistItemEliminado)
                ->exists()
        );
    }

    public function test_permite_asignar_como_dueno_al_responsable_de_la_tarea(): void
    {
        $usuario = User::factory()->create();
        $tarea = Tarea::factory()->create([
            "responsable_id" => $usuario->id,
        ]);

        $this->actingAs($usuario)
            ->post("/tareas/{$tarea->id}/checklist", [
                "texto" => "Revisar materiales",
                "dueno_id" => $usuario->id,
            ])
            ->assertRedirect();

        $item = ChecklistItem::firstOrFail();

        $this->assertSame($usuario->id, $item->dueno_id);
    }

    public function test_permite_asignar_como_dueno_a_un_colaborador(): void
    {
        $responsable = User::factory()->create();
        $colaborador = User::factory()->create();

        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
        ]);

        $tarea->colaboradores()->attach($colaborador->id);

        $this->actingAs($responsable)
            ->post("/tareas/{$tarea->id}/checklist", [
                "texto" => "Revisar documentación",
                "dueno_id" => $colaborador->id,
            ])
            ->assertRedirect();

        $item = ChecklistItem::firstOrFail();

        $this->assertSame($colaborador->id, $item->dueno_id);
    }

    public function test_rechaza_como_dueno_a_un_usuario_que_no_pertenece_a_la_tarea(): void
    {
        $responsable = User::factory()->create();
        $usuarioAjeno = User::factory()->create();

        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
        ]);

        $this->actingAs($responsable)
            ->post("/tareas/{$tarea->id}/checklist", [
                "texto" => "Revisar documentación",
                "dueno_id" => $usuarioAjeno->id,
            ])
            ->assertSessionHas("error");

        $this->assertSame(0, ChecklistItem::count());
    }
}
