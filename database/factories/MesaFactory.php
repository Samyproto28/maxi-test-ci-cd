<?php

namespace Database\Factories;

use App\Models\Mesa;
use App\Models\Provincia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Mesa>
 */
class MesaFactory extends Factory
{
    protected $model = Mesa::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_mesa' => 'MESA-' . fake()->unique()->numberBetween(1000, 9999),
            'provincia_id' => Provincia::factory(),
            'electores' => 1000, // Default for predictable testing
            'circuito' => (string) fake()->numberBetween(1, 100),
            'establecimiento' => fake()->company(),
        ];
    }
}
