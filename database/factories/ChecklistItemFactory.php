<?php

namespace Database\Factories;

use App\Models\Tarea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChecklistItem>
 */
class ChecklistItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            "tarea_id" => Tarea::factory(),
            "texto" => fake()->sentence(3),
            "completado" => false,
            "dueno_id" => null,
        ];
    }

    public function completado(): static
    {
        return $this->state(fn (array $attributes) => [
            "completado" => true,
        ]);
    }
}
