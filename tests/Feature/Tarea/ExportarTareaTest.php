<?php

namespace Tests\Feature\Tarea;

use App\Enums\NivelJerarquico;
use App\Models\ChecklistItem;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * RF-16/24: exportar el registro completo de una tarea (datos generales,
 * subtareas, historial) a PDF y Excel. Se arma una tarea con colaborador +
 * subtarea + reasignacion para ejercitar las 3 tablas, no solo la vacia.
 */
class ExportarTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    private function tareaConContenido(): Tarea
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);
        ChecklistItem::factory()->create([
            "tarea_id" => $tarea->id,
            "texto" => "Revisar planos",
            "dueno_id" => $colaborador->id,
        ]);

        return $tarea;
    }

    public function test_se_puede_exportar_una_tarea_a_pdf(): void
    {
        $tarea = $this->tareaConContenido();

        $response = $this->actingAs($tarea->responsable)->get("/tareas/{$tarea->id}/exportar-pdf");

        $response->assertOk();
        $this->assertSame("application/pdf", $response->headers->get("Content-Type"));
    }

    public function test_se_puede_exportar_una_tarea_a_excel(): void
    {
        $tarea = $this->tareaConContenido();

        $response = $this->actingAs($tarea->responsable)->get("/tareas/{$tarea->id}/exportar-excel");

        $response->assertOk();
        $this->assertStringContainsString(
            "spreadsheetml",
            $response->headers->get("Content-Type"),
        );
    }

    public function test_una_tarea_sin_colaboradores_ni_historial_extra_tambien_se_exporta(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->get("/tareas/{$tarea->id}/exportar-pdf")->assertOk();
        $this->actingAs($responsable)->get("/tareas/{$tarea->id}/exportar-excel")->assertOk();
    }
}
