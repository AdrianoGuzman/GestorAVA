<?php

namespace Database\Seeders;

use App\Enums\NivelJerarquico;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * RF-02: un usuario de prueba por nivel jerarquico, cada uno en una unidad
 * real (sembrada por UnidadOrganizacionalSeeder). Permite dar de alta datos
 * de prueba por nivel/unidad sin tocar la BD a mano (RNF-08) y ejercitar
 * RN-11/RN-12 y la redireccion/menu de RF-03.
 */
class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $directorio = UnidadOrganizacional::where('nombre', 'Directorio AVA')->firstOrFail();
        $gerenciaOperaciones = UnidadOrganizacional::where('nombre', 'Gerencia de Operaciones')->firstOrFail();
        $obraPoniente = UnidadOrganizacional::where('nombre', 'Obra Poniente')->firstOrFail();
        $areaElectrica = UnidadOrganizacional::where('nombre', 'Área Eléctrica')->firstOrFail();

        // El admin ya creado en DatabaseSeeder queda como cuenta de Directorio.
        User::where('email', 'admin@ava.cl')->update([
            'nivel_jerarquico' => NivelJerarquico::Directorio,
            'unidad_organizacional_id' => $directorio->id,
        ]);

        User::factory()->conNivel(NivelJerarquico::Gerencia, $gerenciaOperaciones)->create([
            'nombre_1' => 'Usuario',
            'nombre_2' => 'Prueba',
            'apellido_1' => 'Gerencia',
            'apellido_2' => 'AVA',
            'cargo' => 'Gerente de Operaciones',
            'rut' => '11111111-1',
            'email' => 'gerencia@ava.cl',
            'password' => bcrypt('password'),
        ]);

        User::factory()->conNivel(NivelJerarquico::JefeArea, $obraPoniente)->create([
            'nombre_1' => 'Usuario',
            'nombre_2' => 'Prueba',
            'apellido_1' => 'JefeArea',
            'apellido_2' => 'AVA',
            'cargo' => 'Jefa de Obra Poniente',
            'rut' => '22222222-2',
            'email' => 'jefearea@ava.cl',
            'password' => bcrypt('password'),
        ]);

        User::factory()->conNivel(NivelJerarquico::Asistente, $areaElectrica)->create([
            'nombre_1' => 'Usuario',
            'nombre_2' => 'Prueba',
            'apellido_1' => 'Asistente',
            'apellido_2' => 'AVA',
            'cargo' => 'Electricista',
            'rut' => '33333333-3',
            'email' => 'asistente@ava.cl',
            'password' => bcrypt('password'),
        ]);
    }
}
