<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Notifications\NoParticipacionReportadaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class NoParticipacionTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_avisa_y_se_notifica_al_creador_sin_cambiar_nada(): void
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

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/no-participar", [
            "motivo" => "Estoy con licencia médica las próximas dos semanas.",
        ])->assertRedirect()->assertSessionHas("success");

        $tarea->refresh();
        $this->assertSame(EstadoTarea::EnProgreso, $tarea->estado);
        $this->assertSame($responsable->id, $tarea->responsable_id);

        $evento = $tarea->historial()->where("tipo_evento", TipoEvento::NoParticipacionReportada)->first();
        $this->assertNotNull($evento);
        $this->assertSame("Estoy con licencia médica las próximas dos semanas.", $evento->datos_evento["motivo"]);

        Notification::assertSentTo($creador, NoParticipacionReportadaNotification::class);
    }

    public function test_un_colaborador_avisa_y_se_notifica_al_responsable_sin_sacarlo_de_la_tarea(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        $this->actingAs($colaborador)->patch("/tareas/{$tarea->id}/no-participar", [
            "motivo" => "No tengo el conocimiento técnico para esto.",
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertTrue($tarea->fresh()->colaboradores->contains("id", $colaborador->id));
        Notification::assertSentTo($responsable, NoParticipacionReportadaNotification::class);
        Notification::assertNotSentTo($colaborador, NoParticipacionReportadaNotification::class);
    }

    public function test_el_responsable_no_puede_avisar_si_tambien_es_el_creador(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "creador_id" => $responsable->id,
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/no-participar", [
            "motivo" => "Ya no puedo seguir con esto.",
        ])->assertSessionHas("error");

        $this->assertFalse($tarea->historial()->where("tipo_evento", TipoEvento::NoParticipacionReportada)->exists());
        Notification::assertNothingSent();
    }

    public function test_un_usuario_ajeno_no_puede_avisar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($ajeno)->patch("/tareas/{$tarea->id}/no-participar", [
            "motivo" => "Intento invalido",
        ])->assertSessionHas("error");

        $this->assertFalse($tarea->historial()->where("tipo_evento", TipoEvento::NoParticipacionReportada)->exists());
    }

    public function test_el_motivo_es_obligatorio(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/no-participar", [
            "motivo" => "",
        ])->assertSessionHasErrors("motivo");

        $this->assertFalse($tarea->historial()->where("tipo_evento", TipoEvento::NoParticipacionReportada)->exists());
    }

    public function test_no_se_puede_avisar_sobre_una_tarea_completada(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);

        $this->actingAs($responsable)->patch("/tareas/{$tarea->id}/no-participar", [
            "motivo" => "Intento tardío",
        ])->assertSessionHas("error");

        $this->assertFalse($tarea->historial()->where("tipo_evento", TipoEvento::NoParticipacionReportada)->exists());
    }
}
