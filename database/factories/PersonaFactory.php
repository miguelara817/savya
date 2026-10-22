<?php

namespace Database\Factories;

use App\Models\Persona;
use App\Models\PersonaTipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
class PersonaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'persona_tipo_id' => PersonaTipo::factory(),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'carnet' => fake()->unique()->numerify('########'),
            'telefono' => fake()->numerify('7#######'),
            'correo' => fake()->unique()->safeEmail(),
            'direccion' => fake()->address(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the persona is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
