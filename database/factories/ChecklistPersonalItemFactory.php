<?php

namespace Database\Factories;

use App\Models\Tarea;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ChecklistPersonalItem>
 */
class ChecklistPersonalItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            "tarea_id" => Tarea::factory(),
            "usuario_id" => User::factory(),
            "texto" => fake()->sentence(3),
            "completado" => false,
        ];
    }

    public function completado(): static
    {
        return $this->state(fn (array $attributes) => [
            "completado" => true,
        ]);
    }
}
