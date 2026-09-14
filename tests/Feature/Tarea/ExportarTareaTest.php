<?php

namespace Tests\Feature\Tarea;

use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\ChecklistItem;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Services\ExportacionTareaService;
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

    /**
     * Franco (13-09-2026 D2): "igual de detallado" -- el historial exportado
     * debe traer el mismo diff campo-por-campo que ya muestra
     * historial-timeline.tsx, no solo motivo.
     */
    public function test_el_historial_exportado_solo_detalla_los_campos_que_cambiaron(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->historial()->create([
            "tipo_evento" => TipoEvento::TareaEditada,
            "usuario_id" => $responsable->id,
            "datos_evento" => [
                "datos_anteriores" => [
                    "titulo" => $tarea->titulo,
                    "descripcion" => $tarea->descripcion,
                    "fecha_inicio" => null,
                    "fecha_compromiso" => "2026-09-15",
                    "prioridad" => $tarea->prioridad->value,
                ],
                "titulo" => $tarea->titulo,
                "descripcion" => $tarea->descripcion,
                "fecha_inicio" => null,
                "fecha_compromiso" => "2026-09-22",
                "prioridad" => $tarea->prioridad->value,
            ],
        ]);
        $tarea->load(["historial" => fn ($query) => $query->with("usuario")]);

        $filas = app(ExportacionTareaService::class)->historial($tarea);

        $this->assertCount(1, $filas[0]["detalle"]);
        $this->assertSame("Fecha término: 15-09-2026 → 22-09-2026", $filas[0]["detalle"][0]);
    }

    public function test_el_historial_exportado_resuelve_el_responsable_a_nombre_en_una_reasignacion(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $anterior = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $nuevo = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $nuevo->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->historial()->create([
            "tipo_evento" => TipoEvento::Reasignacion,
            "usuario_id" => $anterior->id,
            "datos_evento" => [
                "responsable_anterior_id" => $anterior->id,
                "responsable_nuevo_id" => $nuevo->id,
                "mantuvo_como_colaborador" => true,
            ],
        ]);
        $tarea->load(["historial" => fn ($query) => $query->with("usuario")]);

        $filas = app(ExportacionTareaService::class)->historial($tarea);

        $this->assertSame("Responsable: {$anterior->name} → {$nuevo->name}", $filas[0]["detalle"][0]);
        $this->assertSame("Responsable saliente: quedó como colaborador", $filas[0]["detalle"][1]);
    }
}
