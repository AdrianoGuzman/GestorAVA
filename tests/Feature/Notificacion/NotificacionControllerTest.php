<?php

namespace Tests\Feature\Notificacion;

use App\Enums\TipoNotificacion;
use App\Models\Tarea;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * RF-15/RF-17: campana de notificaciones -- la parte visible en la
 * plataforma de notificaciones que ya se generaban (atraso, proximo
 * vencimiento, delegacion, etc.) pero no se podian consultar en ningun
 * lado.
 */
class NotificacionControllerTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_lista_solo_las_notificaciones_del_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $otro = User::factory()->create();
        $tarea = Tarea::factory()->create();

        $usuario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Atraso,
            "mensaje" => "La tuya",
        ]);
        $otro->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Atraso,
            "mensaje" => "La de otro",
        ]);

        $response = $this->actingAs($usuario)->getJson("/notificaciones");

        $response->assertOk();
        $response->assertJsonCount(1, "notificaciones");
        $response->assertJsonPath("notificaciones.0.mensaje", "La tuya");
    }

    public function test_marcar_leida_actualiza_el_contador_compartido(): void
    {
        $usuario = User::factory()->create();
        $tarea = Tarea::factory()->create();
        $notificacion = $usuario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::ProximoVencimiento,
            "mensaje" => "Por vencer",
        ]);

        $this->actingAs($usuario)->post("/notificaciones/{$notificacion->id}/leer")->assertOk();

        $this->assertTrue($notificacion->fresh()->leida);
    }

    public function test_no_se_puede_marcar_leida_una_notificacion_ajena(): void
    {
        $usuario = User::factory()->create();
        $otro = User::factory()->create();
        $tarea = Tarea::factory()->create();
        $notificacion = $otro->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Atraso,
            "mensaje" => "Ajena",
        ]);

        $this->actingAs($usuario)->post("/notificaciones/{$notificacion->id}/leer")->assertForbidden();

        $this->assertFalse($notificacion->fresh()->leida);
    }

    public function test_marcar_todas_leidas_solo_afecta_al_usuario_autenticado(): void
    {
        $usuario = User::factory()->create();
        $otro = User::factory()->create();
        $tarea = Tarea::factory()->create();

        $propia = $usuario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Atraso,
            "mensaje" => "Propia",
        ]);
        $ajena = $otro->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Atraso,
            "mensaje" => "Ajena",
        ]);

        $this->actingAs($usuario)->post("/notificaciones/leer-todas")->assertOk();

        $this->assertTrue($propia->fresh()->leida);
        $this->assertFalse($ajena->fresh()->leida);
    }

    public function test_el_contador_compartido_cuenta_solo_las_no_leidas(): void
    {
        $usuario = User::factory()->create();
        $tarea = Tarea::factory()->create();

        $usuario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Atraso,
            "mensaje" => "No leida",
        ]);
        $usuario->notificacionesRecibidas()->create([
            "tarea_id" => $tarea->id,
            "tipo" => TipoNotificacion::Atraso,
            "mensaje" => "Leida",
            "leida" => true,
        ]);

        $response = $this->actingAs($usuario)->get("/dashboard");

        $response->assertInertia(fn ($page) => $page->where("auth.notificacionesNoLeidas", 1));
    }
}
