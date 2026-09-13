<?php

namespace Database\Factories;

use App\Enums\EstadoTarea;
use App\Enums\PrioridadTarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tarea>
 */
class TareaFactory extends Factory
{
    public function definition(): array
    {
        $responsable = User::factory();

        return [
            "titulo" => fake()->sentence(4),
            "descripcion" => fake()->optional()->paragraph(),
            "responsable_id" => $responsable,
            "creador_id" => $responsable,
            "unidad_organizacional_id" => UnidadOrganizacional::factory(),
            "fecha_inicio" => null,
            "fecha_compromiso" => fake()->dateTimeBetween("+1 days", "+30 days"),
            "estado" => EstadoTarea::Pendiente,
            "prioridad" => PrioridadTarea::Media,
            "esta_atrasada" => false,
        ];
    }

    public function prioridadAlta(): static
    {
        return $this->state(fn (array $attributes) => [
            "prioridad" => PrioridadTarea::Alta,
        ]);
    }

    public function prioridadMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            "prioridad" => PrioridadTarea::Media,
        ]);
    }

    public function prioridadBaja(): static
    {
        return $this->state(fn (array $attributes) => [
            "prioridad" => PrioridadTarea::Baja,
        ]);
    }

    public function enProgreso(): static
    {
        return $this->state(fn (array $attributes) => [
            "estado" => EstadoTarea::EnProgreso,
        ]);
    }

    public function completada(): static
    {
        return $this->state(fn (array $attributes) => [
            "estado" => EstadoTarea::Completada,
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            "estado" => EstadoTarea::Cancelada,
            "motivo_cancelacion" => fake()->sentence(),
            "fecha_cancelacion" => now(),
        ]);
    }

    public function atrasada(): static
    {
        return $this->state(fn (array $attributes) => [
            "esta_atrasada" => true,
            "fecha_compromiso" => fake()->dateTimeBetween("-30 days", "-1 days"),
        ]);
    }

    public function hijaDe(\App\Models\Tarea $padre): static
    {
        return $this->state(fn (array $attributes) => [
            "tarea_padre_id" => $padre->id,
            "unidad_organizacional_id" => $padre->unidad_organizacional_id,
        ]);
    }
}
