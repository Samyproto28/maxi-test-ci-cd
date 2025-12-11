<?php

namespace Database\Factories;

use App\Models\Lista;
use App\Models\Provincia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lista>
 */
class ListaFactory extends Factory
{
    protected $model = Lista::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'provincia_id' => Provincia::factory(),
            'cargo' => Lista::CARGO_DIPUTADOS, // Default
            'alianza' => fake()->optional()->company(),
        ];
    }

    /**
     * Indicate that the lista is for senators.
     */
    public function senadores(): static
    {
        return $this->state(fn (array $attributes) => [
            'cargo' => Lista::CARGO_SENADORES,
        ]);
    }
}
