<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class TransicionAutomaticaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    public function test_el_responsable_dispara_la_transicion_al_abrir_con_fecha_inicio_cumplida(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
            "fecha_inicio" => Carbon::yesterday(),
        ]);

        $this->actingAs($responsable)->get("/tareas/{$tarea->id}")->assertOk();

        $tarea->refresh();
        $this->assertSame(EstadoTarea::EnProgreso, $tarea->estado);
        $this->assertTrue($tarea->historial()->where("tipo_evento", TipoEvento::TransicionAutomatica)->exists());
    }

    public function test_un_colaborador_tambien_dispara_la_transicion(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
            "fecha_inicio" => Carbon::today(),
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        $this->actingAs($colaborador)->get("/tareas/{$tarea->id}")->assertOk();

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_sin_fecha_inicio_la_transicion_ocurre_igual(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
            "fecha_inicio" => null,
        ]);

        $this->actingAs($responsable)->get("/tareas/{$tarea->id}")->assertOk();

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }

    public function test_no_transiciona_si_la_fecha_inicio_es_futura(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
            "fecha_inicio" => Carbon::tomorrow(),
        ]);

        $this->actingAs($responsable)->get("/tareas/{$tarea->id}")->assertOk();

        $tarea->refresh();
        $this->assertSame(EstadoTarea::Pendiente, $tarea->estado);
        $this->assertFalse($tarea->historial()->where("tipo_evento", TipoEvento::TransicionAutomatica)->exists());
    }

    public function test_un_usuario_sin_relacion_con_la_tarea_no_dispara_la_transicion(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
            "fecha_inicio" => Carbon::yesterday(),
        ]);

        $this->actingAs($ajeno)->get("/tareas/{$tarea->id}")->assertOk();

        $this->assertSame(EstadoTarea::Pendiente, $tarea->fresh()->estado);
    }

    public function test_no_transiciona_si_la_tarea_ya_no_esta_pendiente(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::EnProgreso,
            "fecha_inicio" => Carbon::yesterday(),
        ]);

        $this->actingAs($responsable)->get("/tareas/{$tarea->id}")->assertOk();

        $this->assertSame(0, $tarea->fresh()->historial()->where("tipo_evento", TipoEvento::TransicionAutomatica)->count());
    }

    public function test_abrir_antes_de_fecha_inicio_no_cambia_nada_pero_abrir_despues_si_transiciona(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Pendiente,
            "fecha_inicio" => Carbon::tomorrow(),
        ]);

        $this->actingAs($responsable)->get("/tareas/{$tarea->id}")->assertOk();
        $this->assertSame(EstadoTarea::Pendiente, $tarea->fresh()->estado);

        $tarea->update(["fecha_inicio" => Carbon::today()]);
        $this->actingAs($responsable)->get("/tareas/{$tarea->id}")->assertOk();

        $this->assertSame(EstadoTarea::EnProgreso, $tarea->fresh()->estado);
    }
}
