<?php

namespace Tests\Feature\Auth;

use App\Enums\NivelJerarquico;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * RF-03: redireccion post-login segun rol y menu por nivel jerarquico.
 * El destino tras el login es el mismo para todos los niveles ("Mis
 * tareas", RF-09 -- no hay paneles separados por nivel en el Sprint 1,
 * eso es RF-20/Fase 2). Lo que cambia por nivel es el conjunto de
 * opciones de menu disponible una vez adentro (D2.1), expuesto al
 * frontend via el prop compartido auth.puedeAdministrarEstructura (ver
 * HandleInertiaRequests).
 */
class RedireccionPostLoginTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_todos_los_niveles_son_redirigidos_a_mis_tareas_tras_el_login(): void
    {
        foreach (NivelJerarquico::cases() as $nivel) {
            $usuario = User::factory()->conNivel($nivel)->create();

            $this->post('/login', [
                'email' => $usuario->email,
                'password' => 'password',
            ])->assertRedirect(route('mis-tareas.index', absolute: false));

            $this->post('/logout');
        }
    }

    public function test_directorio_y_gerencia_ven_la_opcion_de_menu_de_administracion(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $gerencia = User::factory()->conNivel(NivelJerarquico::Gerencia)->create();

        $this->actingAs($directorio)->get('/mis-tareas')
            ->assertInertia(fn ($page) => $page->where('auth.puedeAdministrarEstructura', true));

        $this->actingAs($gerencia)->get('/mis-tareas')
            ->assertInertia(fn ($page) => $page->where('auth.puedeAdministrarEstructura', true));
    }

    public function test_jefe_de_area_y_asistente_no_ven_la_opcion_de_menu_de_administracion(): void
    {
        $jefeArea = User::factory()->conNivel(NivelJerarquico::JefeArea)->create();
        $asistente = User::factory()->conNivel(NivelJerarquico::Asistente)->create();

        $this->actingAs($jefeArea)->get('/mis-tareas')
            ->assertInertia(fn ($page) => $page->where('auth.puedeAdministrarEstructura', false));

        $this->actingAs($asistente)->get('/mis-tareas')
            ->assertInertia(fn ($page) => $page->where('auth.puedeAdministrarEstructura', false));
    }
}
