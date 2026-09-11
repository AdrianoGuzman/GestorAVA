<?php

namespace Tests\Feature\Usuario;

use App\Enums\NivelJerarquico;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

/**
 * RF-02: los seeders son la via elegida (en vez de una pantalla de admin
 * completa, ver RNF-08) para tener datos de prueba por nivel/unidad sin
 * tocar la BD a mano.
 */
class SeedersEstructuraOrganizacionalTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    public function test_el_seeder_deja_exactamente_un_usuario_por_nivel_jerarquico_con_su_unidad(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach (NivelJerarquico::cases() as $nivel) {
            $usuarios = User::where('nivel_jerarquico', $nivel)->get();

            $this->assertCount(1, $usuarios, "Se esperaba exactamente un usuario con nivel {$nivel->value}");
            $this->assertNotNull($usuarios->first()->unidad_organizacional_id);
        }
    }

    public function test_el_admin_de_prueba_queda_en_directorio(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@ava.cl')->firstOrFail();

        $this->assertTrue($admin->nivel_jerarquico === NivelJerarquico::Directorio);
        $this->assertNotNull($admin->unidad_organizacional_id);
    }
}
