<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Notifications\TareaRetrocedidaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class RetrocederTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_puede_retroceder_sin_notificarse_a_si_mismo(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/retroceder", [
            "motivo" => "Prioricé mal el orden de mis tareas.",
        ])->assertRedirect()->assertSessionHas("success");

        $tarea->refresh();
        $this->assertSame(EstadoTarea::Pendiente, $tarea->estado);

        $evento = $tarea->historial()->where("tipo_evento", TipoEvento::Retroceso)->first();
        $this->assertNotNull($evento);
        $this->assertSame("Prioricé mal el orden de mis tareas.", $evento->datos_evento["motivo"]);

        Notification::assertNothingSent();
    }

    public function test_un_colaborador_puede_retroceder_y_notifica_al_responsable(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        $this->actingAs($colaborador)->patch("/tareas/{$tarea->id}/retroceder", [
            "motivo" => "La abrí antes de poder empezarla realmente.",
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertSame(EstadoTarea::Pendiente, $tarea->fresh()->estado);

        Notification::assertSentTo($responsable, TareaRetrocedidaNotification::class);
    }

    public function test_el_responsable_y_los_colaboradores_no_cambian_con_el_retroceso(): void
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

        $this->actingAs($colaborador)->patch("/tareas/{$tarea->id}/retroceder", [
            "motivo" => "Reordenando mi trabajo.",
        ])->assertRedirect();

        $tarea->refresh();
        $this->assertSame($responsable->id, $tarea->responsable_id);
        $this->assertTrue($tarea->colaboradores->contains("id", $colaborador->id));
    }

    public function test_un_usuario_ajeno_no_puede_retroceder(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($ajeno)->patch("/tareas/{$tarea->id}/retroceder", [
            "motivo" => "Intento invalido",
        ])->assertSessionHas("error");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_el_motivo_es_obligatorio(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/retroceder", [
            "motivo" => "",
        ])->assertSessionHasErrors("motivo");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_no_se_puede_retroceder_una_tarea_pendiente(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/retroceder", [
            "motivo" => "Intento invalido",
        ])->assertSessionHasErrors("estado");

        $this->assertSame(EstadoTarea::Pendiente, $tarea->fresh()->estado);
    }

}
