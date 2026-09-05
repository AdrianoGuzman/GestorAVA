<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'nombre_1' => 'Usuario',
            'nombre_2' => 'Prueba',
            'apellido_1' => 'AVA',
            'apellido_2' => 'UCSC',
            'cargo' => 'Administrador',
            'rut' => '12345678-9',
            'email' => 'admin@ava.cl',
            'password' => bcrypt('password'),
        ]);
    }
}
