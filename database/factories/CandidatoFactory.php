<?php

namespace Database\Factories;

use App\Models\Candidato;
use App\Models\Lista;
use App\Models\Provincia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Candidato>
 */
class CandidatoFactory extends Factory
{
    protected $model = Candidato::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'lista_id' => Lista::factory(),
            'provincia_id' => Provincia::factory(),
            'cargo' => fake()->randomElement(Candidato::CARGOS),
            'orden' => fake()->numberBetween(1, 10),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Create a candidato with specific cargo.
     */
    public function withCargo(string $cargo): static
    {
        return $this->state(fn (array $attributes) => [
            'cargo' => $cargo,
        ]);
    }
}
