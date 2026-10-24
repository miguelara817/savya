<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nombre = fake()->unique()->words(3, true);

        return [
            'nombre' => Str::title($nombre),
            'slug' => Str::slug($nombre),
            'codigo' => fake()->unique()->bothify('PRD-####'),
            'caracteristicas' => fake()->sentence(),
            'unidad_medida' => fake()->randomElement(['unidad', 'caja', 'bolsa', 'litro', 'kilo']),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the producto is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
