<?php

namespace Database\Factories;

use App\Enums\NivelJerarquico;
use App\Models\UnidadOrganizacional;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_1' => fake()->firstName(),
            'nombre_2' => fake()->firstName(),
            'apellido_1' => fake()->lastName(),
            'apellido_2' => fake()->lastName(),
            'cargo' => 'Desarrollador',
            'rut' => fake()->unique()->numerify('########-#'),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Asocia al usuario un nivel jerarquico y una unidad organizacional
     * (por defecto una nueva). Sin esto, ambos campos quedan null.
     */
    public function conNivel(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): static
    {
        return $this->state(fn (array $attributes) => [
            'nivel_jerarquico' => $nivel,
            'unidad_organizacional_id' => $unidad?->id ?? UnidadOrganizacional::factory(),
        ]);
    }
}
