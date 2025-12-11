<?php

namespace Tests\Feature\Controllers;

use App\Models\Candidato;
use App\Models\Lista;
use App\Models\Provincia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidatoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_index_returns_paginated_candidatos()
    {
        // Arrange
        Candidato::factory()->count(25)->create();

        // Act
        $response = $this->getJson('/api/v1/candidatos');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'orden',
                        'cargo',
                        'lista' => [
                            'id',
                            'nombre'
                        ],
                        'provincia' => [
                            'id',
                            'nombre'
                        ],
                        'created_at',
                        'updated_at'
                    ]
                ],
                'current_page',
                'per_page',
                'total'
            ]);

        $this->assertCount(15, $response->json('data'));
    }

    /** @test */
    public function test_index_filters_by_lista_id()
    {
        // Arrange
        $lista = Lista::factory()->create();
        Candidato::factory()->count(5)->create(['lista_id' => $lista->id]);
        Candidato::factory()->count(3)->create(); // Other lista

        // Act
        $response = $this->getJson("/api/v1/candidatos?lista_id={$lista->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function test_index_filters_by_provincia_id()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Candidato::factory()->count(5)->create(['provincia_id' => $provincia->id]);
        Candidato::factory()->count(3)->create(); // Other provincia

        // Act
        $response = $this->getJson("/api/v1/candidatos?provincia_id={$provincia->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function test_index_filters_by_cargo()
    {
        // Arrange
        Candidato::factory()->count(5)->create(['cargo' => Candidato::CARGO_DIPUTADOS]);
        Candidato::factory()->count(3)->create(['cargo' => Candidato::CARGO_SENADORES]);

        // Act
        $response = $this->getJson('/api/v1/candidatos?cargo=SENADORES');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $candidato) {
            $this->assertEquals('SENADORES', $candidato['cargo']);
        }
    }

    /** @test */
    public function test_index_with_multiple_filters()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create(['provincia_id' => $provincia->id, 'cargo' => Candidato::CARGO_DIPUTADOS]);
        Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS
        ]);
        Candidato::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_SENADORES
        ]);

        // Act
        $response = $this->getJson("/api/v1/candidatos?provincia_id={$provincia->id}&cargo=DIPUTADOS");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($provincia->id, $response->json('data.0.provincia.id'));
        $this->assertEquals('DIPUTADOS', $response->json('data.0.cargo'));
    }

    /** @test */
    public function test_store_creates_candidato()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS
        ]);

        $data = [
            'nombre' => 'Juan Pérez',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 1,
            'observaciones' => 'Candidato titular'
        ];

        // Act
        $response = $this->postJson('/api/v1/candidatos', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'nombre' => 'Juan Pérez',
                'lista_id' => $lista->id,
                'provincia_id' => $provincia->id,
                'cargo' => Candidato::CARGO_DIPUTADOS,
                'orden' => 1,
                'observaciones' => 'Candidato titular'
            ]);

        $this->assertDatabaseHas('candidatos', $data);
    }

    /** @test */
    public function test_store_returns_422_with_cargo_mismatch()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS
        ]);

        $data = [
            'nombre' => 'Juan Pérez',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_SENADORES, // Mismatch with lista
            'orden' => 1
        ];

        // Act
        $response = $this->postJson('/api/v1/candidatos', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }

    /** @test */
    public function test_store_returns_422_with_duplicate_orden_in_lista()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS
        ]);
        Candidato::factory()->create(['lista_id' => $lista->id, 'orden' => 1]);

        $data = [
            'nombre' => 'Juan Pérez',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 1 // Duplicate orden
        ];

        // Act
        $response = $this->postJson('/api/v1/candidatos', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['orden']);
    }

    /** @test */
    public function test_store_returns_422_with_nonexistent_lista()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $data = [
            'nombre' => 'Juan Pérez',
            'lista_id' => 9999, // Non-existent
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 1
        ];

        // Act
        $response = $this->postJson('/api/v1/candidatos', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['lista_id']);
    }

    /** @test */
    public function test_store_returns_422_with_nonexistent_provincia()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $data = [
            'nombre' => 'Juan Pérez',
            'lista_id' => $lista->id,
            'provincia_id' => 9999, // Non-existent
            'cargo' => $lista->cargo,
            'orden' => 1
        ];

        // Act
        $response = $this->postJson('/api/v1/candidatos', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provincia_id']);
    }

    /** @test */
    public function test_show_returns_candidato_with_lista_provincia()
    {
        // Arrange
        $candidato = Candidato::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/candidatos/{$candidato->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'nombre',
                'orden',
                'cargo',
                'observaciones',
                'lista' => [
                    'id',
                    'nombre',
                    'cargo'
                ],
                'provincia' => [
                    'id',
                    'nombre',
                    'codigo'
                ]
            ]);
    }

    /** @test */
    public function test_show_returns_404_for_nonexistent_candidato()
    {
        // Act
        $response = $this->getJson('/api/v1/candidatos/9999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function test_update_modifies_candidato()
    {
        // Arrange
        $candidato = Candidato::factory()->create(['nombre' => 'Juan Pérez']);
        $data = ['nombre' => 'Juan Carlos Pérez'];

        // Act
        $response = $this->putJson("/api/v1/candidatos/{$candidato->id}", $data);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'nombre' => 'Juan Carlos Pérez'
            ]);

        $this->assertDatabaseHas('candidatos', $data);
    }

    /** @test */
    public function test_update_returns_422_with_duplicate_orden()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $candidato1 = Candidato::factory()->create(['lista_id' => $lista->id, 'orden' => 1]);
        $candidato2 = Candidato::factory()->create(['lista_id' => $lista->id, 'orden' => 2]);

        $data = ['orden' => 1]; // Duplicate

        // Act
        $response = $this->putJson("/api/v1/candidatos/{$candidato2->id}", $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['orden']);
    }

    /** @test */
    public function test_destroy_deletes_candidato()
    {
        // Arrange
        $candidato = Candidato::factory()->create();

        // Act
        $response = $this->deleteJson("/api/v1/candidatos/{$candidato->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('candidatos', ['id' => $candidato->id]);
    }

    /** @test */
    public function test_reordenar_updates_orden_in_transaction()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $candidatos = Candidato::factory()->count(5)->create([
            'lista_id' => $lista->id,
            'cargo' => $lista->cargo,
            'provincia_id' => $lista->provincia_id
        ]);

        $ordenData = [
            'candidatos' => [
                ['id' => $candidatos[0]->id, 'orden' => 5],
                ['id' => $candidatos[1]->id, 'orden' => 3],
                ['id' => $candidatos[2]->id, 'orden' => 1],
                ['id' => $candidatos[3]->id, 'orden' => 4],
                ['id' => $candidatos[4]->id, 'orden' => 2],
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/candidatos/reordenar", $ordenData);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Candidatos reordenados exitosamente'
            ]);

        // Verify all orders were updated
        $candidatos[0]->refresh();
        $candidatos[1]->refresh();
        $candidatos[2]->refresh();
        $candidatos[3]->refresh();
        $candidatos[4]->refresh();

        $this->assertEquals(5, $candidatos[0]->orden);
        $this->assertEquals(3, $candidatos[1]->orden);
        $this->assertEquals(1, $candidatos[2]->orden);
        $this->assertEquals(4, $candidatos[3]->orden);
        $this->assertEquals(2, $candidatos[4]->orden);
    }

    /** @test */
    public function test_reordenar_returns_422_for_candidatos_from_different_listas()
    {
        // Arrange
        $lista1 = Lista::factory()->create();
        $lista2 = Lista::factory()->create();
        $candidato1 = Candidato::factory()->create(['lista_id' => $lista1->id]);
        $candidato2 = Candidato::factory()->create(['lista_id' => $lista2->id]);

        $ordenData = [
            'candidatos' => [
                ['id' => $candidato1->id, 'orden' => 1],
                ['id' => $candidato2->id, 'orden' => 2],
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/candidatos/reordenar", $ordenData);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function test_reordenar_returns_422_for_duplicate_orden()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $candidatos = Candidato::factory()->count(3)->create([
            'lista_id' => $lista->id,
            'cargo' => $lista->cargo,
            'provincia_id' => $lista->provincia_id
        ]);

        $ordenData = [
            'candidatos' => [
                ['id' => $candidatos[0]->id, 'orden' => 1],
                ['id' => $candidatos[1]->id, 'orden' => 2],
                ['id' => $candidatos[2]->id, 'orden' => 2], // Duplicate
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/candidatos/reordenar", $ordenData);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function test_reordenar_returns_422_for_nonexistent_candidatos()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $candidato = Candidato::factory()->create(['lista_id' => $lista->id]);

        $ordenData = [
            'candidatos' => [
                ['id' => $candidato->id, 'orden' => 1],
                ['id' => 9999, 'orden' => 2], // Non-existent
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/candidatos/reordenar", $ordenData);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function test_reordenar_returns_422_for_missing_orden()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $candidatos = Candidato::factory()->count(3)->create([
            'lista_id' => $lista->id,
            'cargo' => $lista->cargo,
            'provincia_id' => $lista->provincia_id
        ]);

        $ordenData = [
            'candidatos' => [
                ['id' => $candidatos[0]->id, 'orden' => 1],
                // Missing orden for $candidatos[1]
                ['id' => $candidatos[2]->id, 'orden' => 2],
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/candidatos/reordenar", $ordenData);

        // Assert
        $response->assertStatus(422);
    }

    /** @test */
    public function test_reordenar_updates_all_candidatos()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $candidatos = Candidato::factory()->count(3)->create([
            'lista_id' => $lista->id,
            'cargo' => $lista->cargo,
            'provincia_id' => $lista->provincia_id
        ]);

        $initialOrders = $candidatos->pluck('orden', 'id')->toArray();

        $ordenData = [
            'candidatos' => [
                ['id' => $candidatos[0]->id, 'orden' => 10],
                ['id' => $candidatos[1]->id, 'orden' => 20],
                ['id' => $candidatos[2]->id, 'orden' => 30],
            ]
        ];

        // Act
        $response = $this->postJson("/api/v1/candidatos/reordenar", $ordenData);

        // Assert
        $response->assertStatus(200);

        // Verify orders were updated
        $candidatos[0]->refresh();
        $candidatos[1]->refresh();
        $candidatos[2]->refresh();

        $this->assertEquals(10, $candidatos[0]->orden);
        $this->assertEquals(20, $candidatos[1]->orden);
        $this->assertEquals(30, $candidatos[2]->orden);
    }
}
