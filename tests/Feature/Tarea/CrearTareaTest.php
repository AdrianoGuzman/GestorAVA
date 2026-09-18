<?php

namespace Tests\Feature\Tarea;

use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\PrioridadTarea;
use App\Enums\TipoEvento;
use App\Models\Proyecto;
use App\Models\Seccion;
use App\Models\Tarea;
use App\Models\User;
use App\Notifications\TareaAsignadaNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class CrearTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function crearUsuarioConUnidad(): User
    {
        return User::factory()->conNivel(NivelJerarquico::Asistente)->create();
    }

    public function test_el_creador_queda_como_responsable_por_defecto_y_se_registra_el_historial(): void
    {
        Notification::fake();

        $creador = $this->crearUsuarioConUnidad();

        $response = $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Instalacion electrica bodega 4",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas("success");

        $tarea = Tarea::firstOrFail();
        $this->assertSame("Instalacion electrica bodega 4", $tarea->titulo);
        $this->assertSame($creador->id, $tarea->creador_id);
        $this->assertSame($creador->id, $tarea->responsable_id);
        $this->assertSame(EstadoTarea::Pendiente, $tarea->estado);
        $this->assertSame($creador->unidad_organizacional_id, $tarea->unidad_organizacional_id);

        $this->assertTrue($tarea->historial()->where("tipo_evento", TipoEvento::Creacion)->exists());

        // No se notifica al creador por asignarse la tarea a si mismo.
        Notification::assertNothingSent();
    }

    public function test_asigna_un_responsable_distinto_y_lo_notifica_sin_notificar_al_creador(): void
    {
        Notification::fake();

        $creador = $this->crearUsuarioConUnidad();
        $responsable = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Instalacion tablero auxiliar",
            "fecha_compromiso" => now()->addDays(3)->toDateString(),
            "responsable_id" => $responsable->id,
        ])->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertSame($responsable->id, $tarea->responsable_id);
        $this->assertSame($responsable->unidad_organizacional_id, $tarea->unidad_organizacional_id);

        Notification::assertSentTo($responsable, TareaAsignadaNotification::class);
        Notification::assertNotSentTo($creador, TareaAsignadaNotification::class);

        $this->assertSame(1, $responsable->notificacionesRecibidas()->count());
    }

    public function test_agrega_colaboradores_sin_cambiar_al_responsable_y_los_notifica(): void
    {
        Notification::fake();

        $creador = $this->crearUsuarioConUnidad();
        $colaborador1 = $this->crearUsuarioConUnidad();
        $colaborador2 = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Instalacion tablero auxiliar",
            "fecha_compromiso" => now()->addDays(3)->toDateString(),
            "colaboradores" => [$colaborador1->id, $colaborador2->id],
        ])->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertSame($creador->id, $tarea->responsable_id);
        $this->assertEqualsCanonicalizing(
            [$colaborador1->id, $colaborador2->id],
            $tarea->colaboradores->pluck("id")->all(),
        );

        Notification::assertSentTo($colaborador1, TareaAsignadaNotification::class);
        Notification::assertSentTo($colaborador2, TareaAsignadaNotification::class);
    }

    public function test_rechaza_la_creacion_sin_titulo(): void
    {
        $creador = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ])->assertSessionHasErrors("titulo");

        $this->assertSame(0, Tarea::count());
    }

    public function test_rechaza_fecha_de_compromiso_anterior_a_hoy(): void
    {
        $creador = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea con fecha invalida",
            "fecha_compromiso" => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors("fecha_compromiso");

        $this->assertSame(0, Tarea::count());
    }

    public function test_rechaza_si_el_responsable_no_tiene_unidad_organizacional_asignada(): void
    {
        $creador = $this->crearUsuarioConUnidad();
        $responsableSinUnidad = User::factory()->create();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea para alguien sin unidad",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
            "responsable_id" => $responsableSinUnidad->id,
        ])->assertSessionHasErrors("responsable_id");

        $this->assertSame(0, Tarea::count());
    }

    public function test_asigna_prioridad_media_por_defecto_si_no_se_especifica(): void
    {
        $creador = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea sin prioridad explicita",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertSame(PrioridadTarea::Media, $tarea->prioridad);
    }

    public function test_permite_elegir_la_prioridad_al_crear(): void
    {
        $creador = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea urgente",
            "fecha_compromiso" => now()->addDays(2)->toDateString(),
            "prioridad" => "alta",
        ])->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertSame(PrioridadTarea::Alta, $tarea->prioridad);
    }

    public function test_rechaza_una_prioridad_invalida(): void
    {
        $creador = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea con prioridad invalida",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
            "prioridad" => "urgentisima",
        ])->assertSessionHasErrors("prioridad");

        $this->assertSame(0, Tarea::count());
    }

    public function test_permite_asociar_la_tarea_a_un_proyecto_al_crearla(): void
    {
        // Gerencia: asociar una tarea a un proyecto esta reservado a
        // Directorio/Gerencia, ver test_un_asistente_no_puede_asociar...
        $creador = User::factory()->conNivel(NivelJerarquico::Gerencia)->create();
        $proyecto = Proyecto::factory()->create();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Firmar acuerdo AVA-ACHS-DEKRA",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
            "proyecto_id" => $proyecto->id,
        ])->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertSame($proyecto->id, $tarea->proyecto_id);
    }

    public function test_rechaza_un_proyecto_inexistente(): void
    {
        $creador = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea con proyecto invalido",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
            "proyecto_id" => 9999,
        ])->assertSessionHasErrors("proyecto_id");

        $this->assertSame(0, Tarea::count());
    }

    /**
     * Franco (14-09-2026): asociar una tarea a un proyecto queda reservado a
     * Directorio/Gerencia -- un Asistente que igual lo manda (saltandose la
     * UI, que ya oculta el campo) simplemente no queda asociado, en vez de
     * rechazar toda la creacion.
     */
    public function test_un_asistente_no_puede_asociar_la_tarea_a_un_proyecto_aunque_lo_mande(): void
    {
        $creador = $this->crearUsuarioConUnidad();
        $proyecto = Proyecto::factory()->create();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Intento de asociar sin permiso",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
            "proyecto_id" => $proyecto->id,
        ])->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertNull($tarea->proyecto_id);
    }

    public function test_permite_asociar_la_tarea_a_una_seccion_de_su_proyecto(): void
    {
        $creador = User::factory()->conNivel(NivelJerarquico::Gerencia)->create();
        $proyecto = Proyecto::factory()->create();
        $seccion = Seccion::factory()->create(["proyecto_id" => $proyecto->id]);

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea con seccion",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
            "proyecto_id" => $proyecto->id,
            "seccion_id" => $seccion->id,
        ])->assertRedirect();

        $tarea = Tarea::firstOrFail();
        $this->assertSame($seccion->id, $tarea->seccion_id);
    }

    public function test_rechaza_una_seccion_que_no_pertenece_al_proyecto_elegido(): void
    {
        $creador = User::factory()->conNivel(NivelJerarquico::Gerencia)->create();
        $proyecto = Proyecto::factory()->create();
        $seccionDeOtroProyecto = Seccion::factory()->create();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea con seccion cruzada",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
            "proyecto_id" => $proyecto->id,
            "seccion_id" => $seccionDeOtroProyecto->id,
        ])->assertSessionHasErrors("seccion_id");

        $this->assertSame(0, Tarea::count());
    }

    public function test_se_puede_marcar_evidencia_obligatoria_al_crear(): void
    {
        $creador = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Instalacion con evidencia obligatoria",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
            "evidencia_obligatoria" => true,
        ])->assertSessionHas("success");

        $tarea = Tarea::firstOrFail();
        $this->assertTrue($tarea->evidencia_obligatoria);
    }

    public function test_evidencia_obligatoria_queda_en_false_por_defecto(): void
    {
        $creador = $this->crearUsuarioConUnidad();

        $this->actingAs($creador)->post("/tareas", [
            "titulo" => "Tarea sin evidencia obligatoria",
            "fecha_compromiso" => now()->addDays(5)->toDateString(),
        ])->assertSessionHas("success");

        $tarea = Tarea::firstOrFail();
        $this->assertFalse($tarea->evidencia_obligatoria);
    }
}
