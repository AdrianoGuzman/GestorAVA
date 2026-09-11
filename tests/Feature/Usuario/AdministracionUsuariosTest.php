<?php

namespace Tests\Feature\Usuario;

use App\Enums\NivelJerarquico;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * RNF-08: administracion minima de la estructura organizacional. Solo
 * Directorio y Gerencia pueden acceder (NivelJerarquico::puedeAdministrarEstructura()).
 */
class AdministracionUsuariosTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_directorio_puede_ver_la_pantalla_de_administracion(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();

        $this->actingAs($directorio)->get('/administracion/usuarios')->assertOk();
    }

    public function test_gerencia_puede_ver_la_pantalla_de_administracion(): void
    {
        $gerencia = User::factory()->conNivel(NivelJerarquico::Gerencia)->create();

        $this->actingAs($gerencia)->get('/administracion/usuarios')->assertOk();
    }

    public function test_jefe_de_area_no_puede_ver_la_pantalla_de_administracion(): void
    {
        $jefeArea = User::factory()->conNivel(NivelJerarquico::JefeArea)->create();

        $this->actingAs($jefeArea)->get('/administracion/usuarios')->assertForbidden();
    }

    public function test_asistente_no_puede_ver_la_pantalla_de_administracion(): void
    {
        $asistente = User::factory()->conNivel(NivelJerarquico::Asistente)->create();

        $this->actingAs($asistente)->get('/administracion/usuarios')->assertForbidden();
    }

    public function test_directorio_puede_crear_un_usuario_con_nivel_y_unidad(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $unidad = UnidadOrganizacional::factory()->create();

        $response = $this->actingAs($directorio)->post('/administracion/usuarios', [
            'nombre_1' => 'Nueva',
            'nombre_2' => 'Persona',
            'apellido_1' => 'De',
            'apellido_2' => 'Prueba',
            'cargo' => 'Soldador',
            'rut' => '99999999-9',
            'email' => 'nueva.persona@ava.cl',
            'password' => 'password123',
            'nivel_jerarquico' => NivelJerarquico::Asistente->value,
            'unidad_organizacional_id' => $unidad->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $usuario = User::where('email', 'nueva.persona@ava.cl')->firstOrFail();
        $this->assertTrue($usuario->nivel_jerarquico === NivelJerarquico::Asistente);
        $this->assertSame($unidad->id, $usuario->unidad_organizacional_id);
    }

    public function test_directorio_puede_editar_el_nivel_y_la_unidad_de_un_usuario_existente(): void
    {
        $directorio = User::factory()->conNivel(NivelJerarquico::Directorio)->create();
        $usuario = User::factory()->conNivel(NivelJerarquico::Asistente)->create();
        $nuevaUnidad = UnidadOrganizacional::factory()->create();

        $response = $this->actingAs($directorio)->patch("/administracion/usuarios/{$usuario->id}", [
            'nivel_jerarquico' => NivelJerarquico::JefeArea->value,
            'unidad_organizacional_id' => $nuevaUnidad->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $usuario->refresh();
        $this->assertTrue($usuario->nivel_jerarquico === NivelJerarquico::JefeArea);
        $this->assertSame($nuevaUnidad->id, $usuario->unidad_organizacional_id);
    }

    public function test_un_jefe_de_area_no_puede_editar_usuarios_aunque_conozca_la_ruta(): void
    {
        $jefeArea = User::factory()->conNivel(NivelJerarquico::JefeArea)->create();
        $usuario = User::factory()->conNivel(NivelJerarquico::Asistente)->create();
        $nivelOriginal = $usuario->nivel_jerarquico;
        $unidadOriginal = $usuario->unidad_organizacional_id;
        $otraUnidad = UnidadOrganizacional::factory()->create();

        $response = $this->actingAs($jefeArea)->patch("/administracion/usuarios/{$usuario->id}", [
            'nivel_jerarquico' => NivelJerarquico::Directorio->value,
            'unidad_organizacional_id' => $otraUnidad->id,
        ]);

        $response->assertSessionHas('error');

        $usuario->refresh();
        $this->assertTrue($usuario->nivel_jerarquico === $nivelOriginal);
        $this->assertSame($unidadOriginal, $usuario->unidad_organizacional_id);
    }

    public function test_un_jefe_de_area_no_puede_crear_usuarios_aunque_conozca_la_ruta(): void
    {
        $jefeArea = User::factory()->conNivel(NivelJerarquico::JefeArea)->create();
        $unidad = UnidadOrganizacional::factory()->create();

        $response = $this->actingAs($jefeArea)->post('/administracion/usuarios', [
            'nombre_1' => 'Nueva',
            'nombre_2' => 'Persona',
            'apellido_1' => 'De',
            'apellido_2' => 'Prueba',
            'cargo' => 'Soldador',
            'rut' => '88888888-8',
            'email' => 'otra.persona@ava.cl',
            'password' => 'password123',
            'nivel_jerarquico' => NivelJerarquico::Asistente->value,
            'unidad_organizacional_id' => $unidad->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'otra.persona@ava.cl'], 'usuarios');
    }
}
