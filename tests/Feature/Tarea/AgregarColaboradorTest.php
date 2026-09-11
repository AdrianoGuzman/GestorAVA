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

class AgregarColaboradorTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_puede_agregar_colaboradores_y_se_les_notifica(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaboradorA = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaboradorB = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/colaboradores", [
            "colaboradores" => [$colaboradorA->id, $colaboradorB->id],
        ])->assertRedirect()->assertSessionHas("success");

        $tarea->refresh();
        $this->assertTrue($tarea->colaboradores->contains("id", $colaboradorA->id));
        $this->assertTrue($tarea->colaboradores->contains("id", $colaboradorB->id));
        $this->assertSame(2, $tarea->historial()->where("tipo_evento", TipoEvento::ColaboradorAgregado)->count());

        Notification::assertSentTo($colaboradorA, TareaAsignadaNotification::class);
        Notification::assertSentTo($colaboradorB, TareaAsignadaNotification::class);
    }

    public function test_un_colaborador_existente_puede_agregar_a_otro(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaboradorExistente = $this->usuario(NivelJerarquico::Asistente, $obra);
        $nuevoColaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($colaboradorExistente->id);

        $this->actingAs($colaboradorExistente)->post("/tareas/{$tarea->id}/colaboradores", [
            "colaboradores" => [$nuevoColaborador->id],
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertTrue($tarea->fresh()->colaboradores->contains("id", $nuevoColaborador->id));
    }

    public function test_un_colaborador_puede_ser_de_otra_unidad_organizacional(): void
    {
        $obraResponsable = UnidadOrganizacional::factory()->create();
        $obraColaborador = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obraResponsable);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obraColaborador);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obraResponsable->id,
        ]);

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/colaboradores", [
            "colaboradores" => [$colaborador->id],
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertTrue($tarea->fresh()->colaboradores->contains("id", $colaborador->id));
    }

    public function test_un_usuario_sin_relacion_con_la_tarea_no_puede_agregar_colaboradores(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $candidato = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($ajeno)->post("/tareas/{$tarea->id}/colaboradores", [
            "colaboradores" => [$candidato->id],
        ])->assertSessionHas("error");

        $this->assertFalse($tarea->fresh()->colaboradores->contains("id", $candidato->id));
    }

    public function test_agregar_un_colaborador_ya_existente_no_genera_duplicado_ni_error(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/colaboradores", [
            "colaboradores" => [$colaborador->id],
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertSame(1, $tarea->fresh()->colaboradores()->where("usuario_id", $colaborador->id)->count());
        $this->assertSame(0, $tarea->historial()->where("tipo_evento", TipoEvento::ColaboradorAgregado)->count());
    }

    public function test_el_responsable_no_puede_quedar_como_su_propio_colaborador(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/colaboradores", [
            "colaboradores" => [$responsable->id],
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertFalse($tarea->fresh()->colaboradores->contains("id", $responsable->id));
    }
}
