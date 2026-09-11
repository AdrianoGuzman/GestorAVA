<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Notifications\TareaRechazadaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class RechazarTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_rechaza_y_vuelve_a_quien_reasigno_por_ultima_vez(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $jefeArea = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        $tarea->historial()->create([
            "tipo_evento" => TipoEvento::Reasignacion,
            "usuario_id" => $jefeArea->id,
            "datos_evento" => ["responsable_nuevo_id" => $responsable->id],
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/rechazar", [
            "motivo" => "El plano recibido está incorrecto.",
        ])->assertRedirect()->assertSessionHas("success");

        $tarea->refresh();
        $this->assertSame(EstadoTarea::Rechazada, $tarea->estado);

        $evento = $tarea->historial()->where("tipo_evento", TipoEvento::Rechazo)->first();
        $this->assertNotNull($evento);
        $this->assertSame("El plano recibido está incorrecto.", $evento->datos_evento["motivo"]);

        Notification::assertSentTo($jefeArea, TareaRechazadaNotification::class);
    }

    public function test_un_colaborador_puede_rechazar_y_no_se_notifica_a_si_mismo(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $jefeArea = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);
        $tarea->historial()->create([
            "tipo_evento" => TipoEvento::Reasignacion,
            "usuario_id" => $jefeArea->id,
            "datos_evento" => ["responsable_nuevo_id" => $responsable->id],
        ]);

        $this->actingAs($colaborador)->patch("/tareas/{$tarea->id}/rechazar", [
            "motivo" => "Información incorrecta.",
        ])->assertRedirect()->assertSessionHas("success");

        Notification::assertSentTo($jefeArea, TareaRechazadaNotification::class);
        Notification::assertNotSentTo($colaborador, TareaRechazadaNotification::class);
    }

    public function test_si_nunca_fue_reasignada_vuelve_al_creador(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $creador = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "creador_id" => $creador->id,
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/rechazar", [
            "motivo" => "Faltan datos.",
        ])->assertRedirect()->assertSessionHas("success");

        Notification::assertSentTo($creador, TareaRechazadaNotification::class);
    }

    public function test_si_el_creador_es_el_propio_responsable_no_hay_a_quien_notificar(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "creador_id" => $responsable->id,
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/rechazar", [
            "motivo" => "Ya no aplica.",
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertSame(EstadoTarea::Rechazada, $tarea->fresh()->estado);
        Notification::assertNothingSent();
    }

    public function test_un_usuario_ajeno_no_puede_rechazar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($ajeno)->patch("/tareas/{$tarea->id}/rechazar", [
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

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/rechazar", [
            "motivo" => "",
        ])->assertSessionHasErrors("motivo");

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_no_se_puede_rechazar_una_tarea_completada(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/rechazar", [
            "motivo" => "Intento invalido",
        ])->assertSessionHasErrors("estado");

        $this->assertSame(EstadoTarea::Completada, $tarea->fresh()->estado);
    }
}
