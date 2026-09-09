<?php

namespace Tests\Feature\Tarea;

use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Notifications\TareaAsignadaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class ReasignarTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_actual_puede_reasignar_y_notifica_al_nuevo(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
        ])->assertRedirect();

        $tarea->refresh();
        $this->assertSame($nuevoResponsable->id, $tarea->responsable_id);
        $this->assertTrue($tarea->historial()->where("tipo_evento", TipoEvento::Reasignacion)->exists());

        Notification::assertSentTo($nuevoResponsable, TareaAsignadaNotification::class);
    }

    public function test_el_superior_directo_de_la_misma_unidad_puede_reasignar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $jefeArea = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($jefeArea)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertSame($nuevoResponsable->id, $tarea->fresh()->responsable_id);
    }

    public function test_el_superior_directo_de_la_unidad_padre_puede_reasignar(): void
    {
        $gerencia = UnidadOrganizacional::factory()->create(["tipo" => "gerencia"]);
        $obra = UnidadOrganizacional::factory()->create(["tipo" => "obra", "unidad_padre_id" => $gerencia->id]);

        $gerente = $this->usuario(NivelJerarquico::Gerencia, $gerencia);
        $responsable = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($gerente)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertSame($nuevoResponsable->id, $tarea->fresh()->responsable_id);
    }

    public function test_un_superior_de_otra_unidad_no_relacionada_no_puede_reasignar(): void
    {
        $obraA = UnidadOrganizacional::factory()->create();
        $obraB = UnidadOrganizacional::factory()->create();

        $jefeDeOtraObra = $this->usuario(NivelJerarquico::JefeArea, $obraB);
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obraA);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obraA);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obraA->id,
        ]);

        $this->actingAs($jefeDeOtraObra)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
        ])->assertRedirect()->assertSessionHas("error");

        $this->assertSame($responsable->id, $tarea->fresh()->responsable_id);
    }

    public function test_un_colaborador_sin_relacion_jerarquica_no_puede_reasignar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($colaborador)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
        ])->assertSessionHas("error");

        $this->assertSame($responsable->id, $tarea->fresh()->responsable_id);
    }

    public function test_el_responsable_saliente_puede_quedar_como_colaborador(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
            "mantener_como_colaborador" => true,
        ])->assertRedirect();

        $this->assertTrue($tarea->fresh()->colaboradores->contains("id", $responsable->id));
    }

    public function test_el_responsable_saliente_no_participa_por_defecto(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
        ])->assertRedirect();

        $this->assertFalse($tarea->fresh()->colaboradores->contains("id", $responsable->id));
    }

    public function test_excepcion_rn12_un_superior_ajeno_a_la_unidad_puede_autorizar_con_motivo(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $otraUnidad = UnidadOrganizacional::factory()->create(["tipo" => "directorio"]);

        $directorAjeno = $this->usuario(NivelJerarquico::Directorio, $otraUnidad);
        $responsable = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($directorAjeno)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
            "es_excepcion" => true,
            "motivo_excepcion" => "Responsable y jefe de area ambos de licencia medica.",
        ])->assertRedirect()->assertSessionHas("success");

        $tarea->refresh();
        $this->assertSame($nuevoResponsable->id, $tarea->responsable_id);

        $evento = $tarea->historial()->where("tipo_evento", TipoEvento::ReasignacionExcepcional)->first();
        $this->assertNotNull($evento);
        $this->assertSame("Responsable y jefe de area ambos de licencia medica.", $evento->datos_evento["motivo_excepcion"]);
    }

    public function test_excepcion_rn12_requiere_motivo(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $director = $this->usuario(NivelJerarquico::Directorio);
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($director)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
            "es_excepcion" => true,
        ])->assertSessionHasErrors("motivo_excepcion");

        $this->assertSame($responsable->id, $tarea->fresh()->responsable_id);
    }

    public function test_excepcion_rn12_rechaza_a_alguien_de_nivel_no_superior_al_responsable(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $otraUnidad = UnidadOrganizacional::factory()->create();

        $mismoNivelOtraUnidad = $this->usuario(NivelJerarquico::JefeArea, $otraUnidad);
        $responsable = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $nuevoResponsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($mismoNivelOtraUnidad)->patch("/tareas/{$tarea->id}/reasignar", [
            "nuevo_responsable_id" => $nuevoResponsable->id,
            "es_excepcion" => true,
            "motivo_excepcion" => "Intento invalido",
        ])->assertSessionHas("error");

        $this->assertSame($responsable->id, $tarea->fresh()->responsable_id);
    }
}
