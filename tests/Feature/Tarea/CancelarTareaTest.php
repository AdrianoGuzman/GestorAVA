<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Notifications\TareaCanceladaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class CancelarTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_puede_cancelar_con_motivo(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/cancelar", [
            "motivo" => "El proyecto que originó esta tarea se dio de baja.",
        ])->assertRedirect()->assertSessionHas("success");

        $tarea->refresh();
        $this->assertSame(EstadoTarea::Cancelada, $tarea->estado);
        $this->assertSame("El proyecto que originó esta tarea se dio de baja.", $tarea->motivo_cancelacion);
        $this->assertNotNull($tarea->fecha_cancelacion);

        $evento = $tarea->historial()->where("tipo_evento", TipoEvento::Cancelacion)->first();
        $this->assertNotNull($evento);
        $this->assertSame("El proyecto que originó esta tarea se dio de baja.", $evento->datos_evento["motivo"]);
    }

    public function test_notifica_a_los_colaboradores_pero_no_al_responsable(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaboradorA = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaboradorB = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        $tarea->colaboradores()->attach([$colaboradorA->id, $colaboradorB->id]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/cancelar", [
            "motivo" => "Ya no es necesaria.",
        ])->assertRedirect();

        Notification::assertSentTo($colaboradorA, TareaCanceladaNotification::class);
        Notification::assertSentTo($colaboradorB, TareaCanceladaNotification::class);
        Notification::assertNotSentTo($responsable, TareaCanceladaNotification::class);
    }

    public function test_un_colaborador_no_puede_cancelar(): void
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

        $this->actingAs($colaborador)->patch("/tareas/{$tarea->id}/cancelar", [
            "motivo" => "Intento invalido",
        ])->assertSessionHas("error");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_un_superior_de_unidad_no_puede_cancelar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $jefeArea = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($jefeArea)->patch("/tareas/{$tarea->id}/cancelar", [
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

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/cancelar", [
            "motivo" => "",
        ])->assertSessionHasErrors("motivo");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_no_se_puede_cancelar_una_tarea_ya_completada(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/cancelar", [
            "motivo" => "Intento invalido",
        ])->assertSessionHasErrors("estado");

        $this->assertSame(EstadoTarea::Completada, $tarea->fresh()->estado);
    }

    public function test_no_se_puede_cancelar_una_tarea_ya_cancelada(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Cancelada,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/cancelar", [
            "motivo" => "Intento invalido",
        ])->assertSessionHasErrors("estado");
    }

}
