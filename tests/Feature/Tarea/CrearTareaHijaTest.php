<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use App\Notifications\DependenciaCreadaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class CrearTareaHijaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_puede_crear_una_tarea_hija_y_queda_registrado_en_el_historial_del_padre(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $padre = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($responsable)->post("/tareas/{$padre->id}/hijas", [
            "titulo" => "Pedir ayuda externa con el cableado",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ])->assertRedirect()->assertSessionHas("success");

        $hija = Tarea::where("titulo", "Pedir ayuda externa con el cableado")->firstOrFail();
        $this->assertSame($padre->id, $hija->tarea_padre_id);
        $this->assertSame($responsable->id, $hija->responsable_id);
        $this->assertSame($responsable->id, $hija->creador_id);

        $this->assertTrue(
            $padre->historial()->where("tipo_evento", TipoEvento::TareaHijaCreada)->exists()
        );

        // El responsable de la tarea padre creo la propia dependencia -- no
        // tiene sentido notificarse a si mismo (EP-20).
        Notification::assertNothingSent();
    }

    public function test_un_colaborador_puede_crear_una_tarea_hija_y_se_notifica_al_responsable_del_padre(): void
    {
        Notification::fake();

        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $padre = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);
        $padre->colaboradores()->attach($colaborador->id);

        $this->actingAs($colaborador)->post("/tareas/{$padre->id}/hijas", [
            "titulo" => "Revisar planos con el proveedor",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertSame(1, Tarea::where("tarea_padre_id", $padre->id)->count());
        Notification::assertSentTo($responsable, DependenciaCreadaNotification::class);
        Notification::assertNotSentTo($colaborador, DependenciaCreadaNotification::class);
    }

    public function test_un_usuario_ajeno_no_puede_crear_una_tarea_hija(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::Asistente, $obra);
        $padre = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
        ]);

        $this->actingAs($ajeno)->post("/tareas/{$padre->id}/hijas", [
            "titulo" => "Tarea hija no autorizada",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ])->assertSessionHas("error");

        $this->assertSame(0, Tarea::where("tarea_padre_id", $padre->id)->count());
    }

    public function test_no_se_pueden_crear_tareas_hijas_de_una_tarea_padre_completada(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $padre = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);

        $this->actingAs($responsable)->post("/tareas/{$padre->id}/hijas", [
            "titulo" => "Tarea hija tardía",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ])->assertSessionHas("error");

        $this->assertSame(0, Tarea::where("tarea_padre_id", $padre->id)->count());
    }

    public function test_no_se_pueden_crear_tareas_hijas_de_una_tarea_padre_cancelada(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $padre = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Cancelada,
        ]);

        $this->actingAs($responsable)->post("/tareas/{$padre->id}/hijas", [
            "titulo" => "Tarea hija tardía",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ])->assertSessionHas("error");

        $this->assertSame(0, Tarea::where("tarea_padre_id", $padre->id)->count());
    }
}
