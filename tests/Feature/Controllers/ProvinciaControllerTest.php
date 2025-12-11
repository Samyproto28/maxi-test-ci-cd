<?php

namespace Tests\Feature\Controllers;

use App\Models\Provincia;
use App\Models\Lista;
use App\Models\Candidato;
use App\Models\Mesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvinciaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_index_returns_paginated_provincias()
    {
        // Arrange
        Provincia::factory()->count(25)->create();

        // Act
        $response = $this->getJson('/api/v1/provincias');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'codigo',
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
    public function test_index_with_search_filters_by_nombre()
    {
        // Arrange
        Provincia::factory()->create(['nombre' => 'Buenos Aires']);
        Provincia::factory()->create(['nombre' => 'Córdoba']);
        Provincia::factory()->create(['nombre' => 'Mendoza']);

        // Act
        $response = $this->getJson('/api/v1/provincias?search=Córdoba');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Córdoba', $response->json('data.0.nombre'));
    }

    /** @test */
    public function test_index_with_search_filters_by_codigo()
    {
        // Arrange
        Provincia::factory()->create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        Provincia::factory()->create(['nombre' => 'Córdoba', 'codigo' => 'CB']);
        Provincia::factory()->create(['nombre' => 'Mendoza', 'codigo' => 'MZ']);

        // Act
        $response = $this->getJson('/api/v1/provincias?search=BA');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('BA', $response->json('data.0.codigo'));
    }

    /** @test */
    public function test_index_with_custom_per_page()
    {
        // Arrange
        Provincia::factory()->count(10)->create();

        // Act
        $response = $this->getJson('/api/v1/provincias?per_page=5');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
        $this->assertEquals(5, $response->json('per_page'));
    }

    /** @test */
    public function test_store_creates_provincia_with_valid_data()
    {
        // Arrange
        $data = [
            'nombre' => 'Tierra del Fuego',
            'codigo' => 'TF'
        ];

        // Act
        $response = $this->postJson('/api/v1/provincias', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'nombre' => 'Tierra del Fuego',
                'codigo' => 'TF'
            ]);

        $this->assertDatabaseHas('provincias', $data);
    }

    /** @test */
    public function test_store_returns_422_with_missing_nombre()
    {
        // Arrange
        $data = ['codigo' => 'TF'];

        // Act
        $response = $this->postJson('/api/v1/provincias', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    /** @test */
    public function test_store_returns_422_with_duplicate_nombre()
    {
        // Arrange
        Provincia::factory()->create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $data = ['nombre' => 'Buenos Aires', 'codigo' => 'TF'];

        // Act
        $response = $this->postJson('/api/v1/provincias', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    /** @test */
    public function test_store_returns_422_with_duplicate_codigo()
    {
        // Arrange
        Provincia::factory()->create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $data = ['nombre' => 'Tierra del Fuego', 'codigo' => 'BA'];

        // Act
        $response = $this->postJson('/api/v1/provincias', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codigo']);
    }

    /** @test */
    public function test_store_returns_422_with_invalid_codigo_format()
    {
        // Arrange
        $data = ['nombre' => 'Tierra del Fuego', 'codigo' => 'toolong'];

        // Act
        $response = $this->postJson('/api/v1/provincias', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codigo']);
    }

    /** @test */
    public function test_show_returns_provincia()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/provincias/{$provincia->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'id' => $provincia->id,
                'nombre' => $provincia->nombre,
                'codigo' => $provincia->codigo
            ]);
    }

    /** @test */
    public function test_show_returns_404_for_nonexistent_provincia()
    {
        // Act
        $response = $this->getJson('/api/v1/provincias/9999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function test_update_modifies_provincia()
    {
        // Arrange
        $provincia = Provincia::factory()->create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $data = ['nombre' => 'Provincia de Buenos Aires', 'codigo' => 'PBA'];

        // Act
        $response = $this->putJson("/api/v1/provincias/{$provincia->id}", $data);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'nombre' => 'Provincia de Buenos Aires',
                'codigo' => 'PBA'
            ]);

        $this->assertDatabaseHas('provincias', $data);
    }

    /** @test */
    public function test_update_returns_422_with_duplicate_nombre()
    {
        // Arrange
        Provincia::factory()->create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        Provincia::factory()->create(['nombre' => 'Córdoba', 'codigo' => 'CB']);
        $provincia = Provincia::factory()->create(['nombre' => 'Mendoza', 'codigo' => 'MZ']);

        $data = ['nombre' => 'Buenos Aires', 'codigo' => 'MZ'];

        // Act
        $response = $this->putJson("/api/v1/provincias/{$provincia->id}", $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    /** @test */
    public function test_update_returns_422_with_duplicate_codigo()
    {
        // Arrange
        Provincia::factory()->create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        Provincia::factory()->create(['nombre' => 'Córdoba', 'codigo' => 'CB']);
        $provincia = Provincia::factory()->create(['nombre' => 'Mendoza', 'codigo' => 'MZ']);

        $data = ['nombre' => 'Mendoza', 'codigo' => 'BA'];

        // Act
        $response = $this->putJson("/api/v1/provincias/{$provincia->id}", $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codigo']);
    }

    /** @test */
    public function test_destroy_deletes_provincia()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $response = $this->deleteJson("/api/v1/provincias/{$provincia->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('provincias', ['id' => $provincia->id]);
    }

    /** @test */
    public function test_destroy_returns_400_when_has_related_records()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Lista::factory()->create(['provincia_id' => $provincia->id]);

        // Act
        $response = $this->deleteJson("/api/v1/provincias/{$provincia->id}");

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'No se puede eliminar la provincia porque tiene registros asociados'
            ]);

        $this->assertDatabaseHas('provincias', ['id' => $provincia->id]);
    }

    /** @test */
    public function test_destroy_deletes_provincia_with_cascade_relations()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Create related data
        $lista = Lista::factory()->create(['provincia_id' => $provincia->id]);
        $candidato = Candidato::factory()->create(['lista_id' => $lista->id, 'provincia_id' => $provincia->id]);
        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);

        // Act - Force delete
        $response = $this->deleteJson("/api/v1/provincias/{$provincia->id}?force=1");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('provincias', ['id' => $provincia->id]);
        $this->assertDatabaseMissing('listas', ['id' => $lista->id]);
        $this->assertDatabaseMissing('candidatos', ['id' => $candidato->id]);
        $this->assertDatabaseMissing('mesas', ['id' => $mesa->id]);
    }
}
