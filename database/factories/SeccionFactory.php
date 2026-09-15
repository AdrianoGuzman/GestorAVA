<?php

namespace Database\Factories;

use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Seccion>
 */
class SeccionFactory extends Factory
{
    public function definition(): array
    {
        return [
            "proyecto_id" => Proyecto::factory(),
            "nombre" => fake()->unique()->words(3, true),
            "peso" => fake()->randomFloat(3, 0.1, 1),
        ];
    }
}
