<?php

namespace Database\Factories;

use App\Models\PersonaTipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonaTipo>
 */
class PersonaTipoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'valor' => fake()->unique()->word(),
        ];
    }
}
