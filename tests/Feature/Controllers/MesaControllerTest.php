<?php

namespace Tests\Feature\Controllers;

use App\Models\Mesa;
use App\Models\Provincia;
use App\Models\Telegrama;
use App\Models\Lista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_index_returns_paginated_mesas_with_telegramas_count()
    {
        // Arrange
        Mesa::factory()->count(25)->create();

        // Act
        $response = $this->getJson('/api/v1/mesas');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'id_mesa',
                        'circuito',
                        'establecimiento',
                        'electores',
                        'provincia' => [
                            'id',
                            'nombre',
                            'codigo'
                        ],
                        'telegramas_count',
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
        Mesa::factory()->count(5)->create(['provincia_id' => $provincia1->id]);
        Mesa::factory()->count(3)->create(['provincia_id' => $provincia2->id]);

        // Act
        $response = $this->getJson("/api/v1/mesas?provincia_id={$provincia1->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function test_index_filters_by_circuito()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Mesa::factory()->count(5)->create(['provincia_id' => $provincia->id, 'circuito' => '001']);
        Mesa::factory()->count(3)->create(['provincia_id' => $provincia->id, 'circuito' => '002']);

        // Act
        $response = $this->getJson('/api/v1/mesas?circuito=001');

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $mesa) {
            $this->assertEquals('001', $mesa['circuito']);
        }
    }

    /** @test */
    public function test_index_filters_by_id_mesa()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Mesa::factory()->create(['provincia_id' => $provincia->id, 'id_mesa' => 'MESA-001']);
        Mesa::factory()->create(['provincia_id' => $provincia->id, 'id_mesa' => 'MESA-002']);

        // Act
        $response = $this->getJson('/api/v1/mesas?id_mesa=MESA-002');

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('MESA-002', $response->json('data.0.id_mesa'));
    }

    /** @test */
    public function test_index_with_multiple_filters()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'circuito' => '001',
            'id_mesa' => 'MESA-001'
        ]);
        Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'circuito' => '002',
            'id_mesa' => 'MESA-002'
        ]);

        // Act
        $response = $this->getJson("/api/v1/mesas?provincia_id={$provincia->id}&circuito=001");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('001', $response->json('data.0.circuito'));
    }

    /** @test */
    public function test_store_creates_mesa()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $data = [
            'id_mesa' => 'MESA-001',
            'provincia_id' => $provincia->id,
            'circuito' => '001',
            'establecimiento' => 'Escuela Nacional',
            'electores' => 350
        ];

        // Act
        $response = $this->postJson('/api/v1/mesas', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'id_mesa' => 'MESA-001',
                'provincia_id' => $provincia->id,
                'circuito' => '001',
                'establecimiento' => 'Escuela Nacional',
                'electores' => 350
            ]);

        $this->assertDatabaseHas('mesas', $data);
    }

    /** @test */
    public function test_store_returns_422_with_duplicate_id_mesa_in_provincia()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'id_mesa' => 'MESA-001'
        ]);

        $data = [
            'id_mesa' => 'MESA-001',
            'provincia_id' => $provincia->id,
            'circuito' => '001',
            'electores' => 300
        ];

        // Act
        $response = $this->postJson('/api/v1/mesas', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_mesa']);
    }

    /** @test */
    public function test_store_allows_same_id_mesa_different_provincia()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();
        Mesa::factory()->create([
            'provincia_id' => $provincia1->id,
            'id_mesa' => 'MESA-001'
        ]);

        $data = [
            'id_mesa' => 'MESA-001',
            'provincia_id' => $provincia2->id,
            'circuito' => '001',
            'electores' => 300
        ];

        // Act
        $response = $this->postJson('/api/v1/mesas', $data);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('mesas', $data);
    }

    /** @test */
    public function test_store_returns_422_with_nonexistent_provincia()
    {
        // Arrange
        $data = [
            'id_mesa' => 'MESA-001',
            'provincia_id' => 9999, // Non-existent
            'circuito' => '001',
            'electores' => 300
        ];

        // Act
        $response = $this->postJson('/api/v1/mesas', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provincia_id']);
    }

    /** @test */
    public function test_store_returns_422_with_invalid_electores()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $data = [
            'id_mesa' => 'MESA-001',
            'provincia_id' => $provincia->id,
            'circuito' => '001',
            'electores' => 0 // Invalid: must be > 0
        ];

        // Act
        $response = $this->postJson('/api/v1/mesas', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['electores']);
    }

    /** @test */
    public function test_show_returns_mesa_with_telegramas()
    {
        // Arrange
        $mesa = Mesa::factory()->create();
        $lista = Lista::factory()->create(['provincia_id' => $mesa->provincia_id]);
        Telegrama::factory()->create(['mesa_id' => $mesa->id, 'lista_id' => $lista->id]);

        // Act
        $response = $this->getJson("/api/v1/mesas/{$mesa->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'id_mesa',
                'circuito',
                'establecimiento',
                'electores',
                'provincia' => [
                    'id',
                    'nombre',
                    'codigo'
                ],
                'telegramas' => [
                    '*' => [
                        'id',
                        'votos_diputados',
                        'votos_senadores',
                        'lista' => [
                            'id',
                            'nombre'
                        ]
                    ]
                ]
            ]);

        $this->assertCount(1, $response->json('telegramas'));
    }

    /** @test */
    public function test_show_returns_404_for_nonexistent_mesa()
    {
        // Act
        $response = $this->getJson('/api/v1/mesas/9999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function test_update_modifies_mesa()
    {
        // Arrange
        $mesa = Mesa::factory()->create([
            'establecimiento' => 'Escuela A',
            'electores' => 300
        ]);
        $data = [
            'establecimiento' => 'Escuela B',
            'electores' => 350
        ];

        // Act
        $response = $this->putJson("/api/v1/mesas/{$mesa->id}", $data);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'establecimiento' => 'Escuela B',
                'electores' => 350
            ]);

        $this->assertDatabaseHas('mesas', $data);
    }

    /** @test */
    public function test_update_returns_422_with_duplicate_id_mesa()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $mesa1 = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'id_mesa' => 'MESA-001'
        ]);
        $mesa2 = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'id_mesa' => 'MESA-002'
        ]);

        $data = ['id_mesa' => 'MESA-001']; // Duplicate

        // Act
        $response = $this->putJson("/api/v1/mesas/{$mesa2->id}", $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['id_mesa']);
    }

    /** @test */
    public function test_destroy_deletes_mesa()
    {
        // Arrange
        $mesa = Mesa::factory()->create();

        // Act
        $response = $this->deleteJson("/api/v1/mesas/{$mesa->id}");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('mesas', ['id' => $mesa->id]);
    }

    /** @test */
    public function test_destroy_returns_422_when_has_telegramas()
    {
        // Arrange
        $mesa = Mesa::factory()->create();
        $lista = Lista::factory()->create(['provincia_id' => $mesa->provincia_id]);
        Telegrama::factory()->create(['mesa_id' => $mesa->id, 'lista_id' => $lista->id]);

        // Act
        $response = $this->deleteJson("/api/v1/mesas/{$mesa->id}");

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'error' => 'No se puede eliminar la mesa porque tiene telegramas asociados'
            ]);

        $this->assertDatabaseHas('mesas', ['id' => $mesa->id]);
    }

    /** @test */
    public function test_destroy_deletes_mesa_with_cascade_telegramas()
    {
        // Arrange
        $mesa = Mesa::factory()->create();
        $lista = Lista::factory()->create(['provincia_id' => $mesa->provincia_id]);
        $telegrama = Telegrama::factory()->create(['mesa_id' => $mesa->id, 'lista_id' => $lista->id]);

        // Act - Force delete
        $response = $this->deleteJson("/api/v1/mesas/{$mesa->id}?force=1");

        // Assert
        $response->assertStatus(204);
        $this->assertDatabaseMissing('mesas', ['id' => $mesa->id]);
        $this->assertDatabaseMissing('telegramas', ['id' => $telegrama->id]);
    }

    /** @test */
    public function test_mesas_by_provincia_returns_filtered_mesas()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();
        Mesa::factory()->count(5)->create(['provincia_id' => $provincia1->id]);
        Mesa::factory()->count(3)->create(['provincia_id' => $provincia2->id]);

        // Act
        $response = $this->getJson("/api/v1/mesas/provincia/{$provincia1->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(5, $response->json());
    }

    /** @test */
    public function test_mesas_by_provincia_filters_by_circuito()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Mesa::factory()->count(5)->create([
            'provincia_id' => $provincia->id,
            'circuito' => '001'
        ]);
        Mesa::factory()->count(3)->create([
            'provincia_id' => $provincia->id,
            'circuito' => '002'
        ]);

        // Act
        $response = $this->getJson("/api/v1/mesas/provincia/{$provincia->id}?circuito=002");

        // Assert
        $response->assertStatus(200);
        $data = $response->json();
        foreach ($data as $mesa) {
            $this->assertEquals('002', $mesa['circuito']);
        }
    }

    /** @test */
    public function test_mesas_by_provincia_includes_telegramas_count()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        $lista = Lista::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->count(3)->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id
        ]);

        // Act
        $response = $this->getJson("/api/v1/mesas/provincia/{$provincia->id}");

        // Assert
        $response->assertStatus(200);
        $mesaData = collect($response->json())->firstWhere('id', $mesa->id);
        $this->assertEquals(3, $mesaData['telegramas_count']);
    }

    /** @test */
    public function test_mesas_by_provincia_returns_empty_when_no_results()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/mesas/provincia/{$provincia->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    /** @test */
    public function test_mesas_by_provincia_returns_404_for_nonexistent_provincia()
    {
        // Act
        $response = $this->getJson('/api/v1/mesas/provincia/9999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function test_index_orders_by_id_mesa_asc()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        Mesa::factory()->create(['provincia_id' => $provincia->id, 'id_mesa' => 'MESA-003']);
        Mesa::factory()->create(['provincia_id' => $provincia->id, 'id_mesa' => 'MESA-001']);
        Mesa::factory()->create(['provincia_id' => $provincia->id, 'id_mesa' => 'MESA-002']);

        // Act
        $response = $this->getJson('/api/v1/mesas');

        // Assert
        $response->assertStatus(200);
        $mesas = $response->json('data');
        $this->assertEquals('MESA-001', $mesas[0]['id_mesa']);
        $this->assertEquals('MESA-002', $mesas[1]['id_mesa']);
        $this->assertEquals('MESA-003', $mesas[2]['id_mesa']);
    }
}
