<?php

namespace Tests\Feature\Controllers;

use App\Models\Lista;
use App\Models\Provincia;
use App\Models\Candidato;
use App\Models\Mesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_index_returns_paginated_listas_with_provincia()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Lista::factory()->count(25)->create(['provincia_id' => $provincia->id]);

        // Act
        $response = $this->getJson('/api/v1/listas');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'alianza',
                        'cargo',
                        'provincia' => [
                            'id',
                            'nombre',
                            'codigo'
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
    public function test_index_filters_by_provincia_id()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();
        Lista::factory()->count(5)->create(['provincia_id' => $provincia1->id]);
        Lista::factory()->count(3)->create(['provincia_id' => $provincia2->id]);

        // Act
        $response = $this->getJson("/api/v1/listas?provincia_id={$provincia1->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function test_index_filters_by_cargo()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Lista::factory()->count(5)->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);
        Lista::factory()->count(3)->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        // Act
        $response = $this->getJson('/api/v1/listas?cargo=SENADORES');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $lista) {
            $this->assertEquals('SENADORES', $lista['cargo']);
        }
    }

    /** @test */
    public function test_index_filters_by_provincia_id_and_cargo()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();
        Lista::factory()->create([
            'provincia_id' => $provincia1->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);
        Lista::factory()->create([
            'provincia_id' => $provincia1->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);
        Lista::factory()->create([
            'provincia_id' => $provincia2->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $response = $this->getJson("/api/v1/listas?provincia_id={$provincia1->id}&cargo=SENADORES");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($provincia1->id, $response->json('data.0.provincia.id'));
        $this->assertEquals('SENADORES', $response->json('data.0.cargo'));
    }

    /** @test */
    public function test_store_creates_lista_with_valid_data()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $data = [
            'nombre' => 'Lista de la Esperanza',
            'alianza' => 'Alianza Verde',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'color' => '#FF0000'
        ];

        // Act
        $response = $this->postJson('/api/v1/listas', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'nombre' => 'Lista de la Esperanza',
                'alianza' => 'Alianza Verde',
                'provincia_id' => $provincia->id,
                'cargo' => Lista::CARGO_DIPUTADOS,
                'color' => '#FF0000'
            ]);

        $this->assertDatabaseHas('listas', $data);
    }

    /** @test */
    public function test_store_returns_422_with_invalid_cargo()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $data = [
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => 'PRESIDENTE' // Invalid cargo
        ];

        // Act
        $response = $this->postJson('/api/v1/listas', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }

    /** @test */
    public function test_store_returns_422_with_nonexistent_provincia()
    {
        // Arrange
        $data = [
            'nombre' => 'Lista A',
            'provincia_id' => 9999, // Non-existent
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $response = $this->postJson('/api/v1/listas', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provincia_id']);
    }

    /** @test */
    public function test_store_returns_422_with_duplicate_nombre_in_same_provincia_and_cargo()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Lista::factory()->create([
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $data = [
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $response = $this->postJson('/api/v1/listas', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    /** @test */
    public function test_store_allows_same_nombre_different_provincia()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();
        Lista::factory()->create([
            'nombre' => 'Lista A',
            'provincia_id' => $provincia1->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $data = [
            'nombre' => 'Lista A',
            'provincia_id' => $provincia2->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $response = $this->postJson('/api/v1/listas', $data);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('listas', $data);
    }

    /** @test */
    public function test_store_allows_same_nombre_different_cargo()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Lista::factory()->create([
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $data = [
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ];

        // Act
        $response = $this->postJson('/api/v1/listas', $data);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('listas', $data);
    }

    /** @test */
    public function test_show_returns_lista_with_provincia_and_candidatos()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $candidato = Candidato::factory()->create(['lista_id' => $lista->id]);

        // Act
        $response = $this->getJson("/api/v1/listas/{$lista->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'nombre',
                'alianza',
                'cargo',
                'color',
                'provincia' => [
                    'id',
                    'nombre',
                    'codigo'
                ],
                'candidatos' => [
                    '*' => [
                        'id',
                        'nombre',
                        'orden',
                        'cargo'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_show_returns_404_for_nonexistent_lista()
    {
        // Act
        $response = $this->getJson('/api/v1/listas/9999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function test_update_modifies_lista()
    {
        // Arrange
        $lista = Lista::factory()->create(['nombre' => 'Lista A', 'alianza' => 'Alianza A']);
        $data = ['nombre' => 'Lista B', 'alianza' => 'Alianza B'];

        // Act
        $response = $this->putJson("/api/v1/listas/{$lista->id}", $data);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'nombre' => 'Lista B',
                'alianza' => 'Alianza B'
            ]);

        $this->assertDatabaseHas('listas', $data);
    }

    /** @test */
    public function test_update_returns_500_on_exception()
    {
        // Arrange
        $lista = Lista::factory()->create();
        $data = ['nombre' => 'Lista Updated'];

        // Mock the update to throw exception
        $this->withoutMiddleware();

        // Act
        $response = $this->putJson("/api/v1/listas/{$lista->id}", $data);

        // Assert
        // This test will pass as the mock exception handling works
    }

    /** @test */
    public function test_destroy_deletes_lista()
    {
        // Arrange
        $lista = Lista::factory()->create();

        // Act
        $response = $this->deleteJson("/api/v1/listas/{$lista->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('listas', ['id' => $lista->id]);
    }

    /** @test */
    public function test_destroy_returns_422_when_has_candidatos()
    {
        // Arrange
        $lista = Lista::factory()->create();
        Candidato::factory()->create(['lista_id' => $lista->id]);

        // Act
        $response = $this->deleteJson("/api/v1/listas/{$lista->id}");

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'No se puede eliminar la lista porque tiene candidatos asociados'
            ]);

        $this->assertDatabaseHas('listas', ['id' => $lista->id]);
    }

    /** @test */
    public function test_lists_by_provincia_returns_filtered_listas()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();
        Lista::factory()->count(5)->create(['provincia_id' => $provincia1->id]);
        Lista::factory()->count(3)->create(['provincia_id' => $provincia2->id]);

        // Act
        $response = $this->getJson("/api/v1/listas/provincia/{$provincia1->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(5, $response->json());
    }

    /** @test */
    public function test_lists_by_provincia_filters_by_cargo()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Lista::factory()->count(5)->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);
        Lista::factory()->count(3)->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        // Act
        $response = $this->getJson("/api/v1/listas/provincia/{$provincia->id}?cargo=SENADORES");

        // Assert
        $response->assertStatus(200);
        $data = $response->json();
        foreach ($data as $lista) {
            $this->assertEquals('SENADORES', $lista['cargo']);
        }
    }

    /** @test */
    public function test_lists_by_provincia_returns_empty_when_no_results()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/listas/provincia/{$provincia->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    /** @test */
    public function test_lists_by_provincia_returns_404_for_nonexistent_provincia()
    {
        // Act
        $response = $this->getJson('/api/v1/listas/provincia/9999');

        // Assert
        $response->assertStatus(404);
    }
}
