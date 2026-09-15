<?php

namespace Database\Factories;

use App\Enums\EstadoProyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Proyecto>
 */
class ProyectoFactory extends Factory
{
    public function definition(): array
    {
        return [
            "nombre" => fake()->unique()->catchPhrase(),
            "descripcion" => fake()->sentence(),
            "creador_id" => User::factory(),
            "estado" => EstadoProyecto::Activo,
            "fecha_inicio" => now()->toDateString(),
            "fecha_termino" => now()->addMonths(6)->toDateString(),
        ];
    }
}
