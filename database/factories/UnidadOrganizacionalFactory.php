<?php

namespace Database\Factories;

use App\Enums\TipoUnidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnidadOrganizacional>
 */
class UnidadOrganizacionalFactory extends Factory
{
    public function definition(): array
    {
        return [
            "nombre" => fake()->unique()->company(),
            "tipo" => fake()->randomElement(TipoUnidad::cases()),
            "unidad_padre_id" => null,
        ];
    }
}
