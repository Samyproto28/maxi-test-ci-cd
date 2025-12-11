<?php

namespace Database\Factories;

use App\Models\Telegrama;
use App\Models\Mesa;
use App\Models\Lista;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Telegrama>
 */
class TelegramaFactory extends Factory
{
    protected $model = Telegrama::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mesa_id' => Mesa::factory(),
            'lista_id' => Lista::factory(),
            'votos_diputados' => fake()->numberBetween(0, 200),
            'votos_senadores' => fake()->numberBetween(0, 200),
            'blancos' => fake()->numberBetween(0, 50),
            'nulos' => fake()->numberBetween(0, 30),
            'recurridos' => fake()->numberBetween(0, 20),
            'usuario' => fake()->userName(),
        ];
    }

    /**
     * Create a telegrama with specific vote totals for testing.
     */
    public function withVotes(
        int $diputados,
        int $senadores,
        int $blancos = 0,
        int $nulos = 0,
        int $recurridos = 0
    ): static {
        return $this->state(fn (array $attributes) => [
            'votos_diputados' => $diputados,
            'votos_senadores' => $senadores,
            'blancos' => $blancos,
            'nulos' => $nulos,
            'recurridos' => $recurridos,
        ]);
    }
}
