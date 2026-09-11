<?php

namespace Database\Seeders;

use App\Enums\TipoUnidad;
use App\Models\UnidadOrganizacional;
use Illuminate\Database\Seeder;

/**
 * RF-02: datos de prueba minimos para poder ejercitar RN-11 (superior
 * jerarquico directo de la MISMA unidad) y RN-12 (excepcion por ausencia)
 * con una jerarquia real de mas de un nivel de profundidad.
 */
class UnidadOrganizacionalSeeder extends Seeder
{
    public function run(): void
    {
        $directorio = UnidadOrganizacional::create([
            'nombre' => 'Directorio AVA',
            'tipo' => TipoUnidad::Directorio,
            'unidad_padre_id' => null,
        ]);

        $gerenciaOperaciones = UnidadOrganizacional::create([
            'nombre' => 'Gerencia de Operaciones',
            'tipo' => TipoUnidad::Gerencia,
            'unidad_padre_id' => $directorio->id,
        ]);

        UnidadOrganizacional::create([
            'nombre' => 'Obra Poniente',
            'tipo' => TipoUnidad::Obra,
            'unidad_padre_id' => $gerenciaOperaciones->id,
        ]);

        UnidadOrganizacional::create([
            'nombre' => 'Área Eléctrica',
            'tipo' => TipoUnidad::Area,
            'unidad_padre_id' => $gerenciaOperaciones->id,
        ]);
    }
}
