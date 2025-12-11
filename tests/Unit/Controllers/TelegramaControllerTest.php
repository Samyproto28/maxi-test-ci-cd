<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\Api\TelegramaController;
use App\Models\{Telegrama, Mesa, Lista};
use App\Services\TelegramaValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelegramaControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_controller_can_be_instantiated(): void
    {
        // Arrange
        $service = app(TelegramaValidationService::class);

        // Act
        $controller = new TelegramaController($service);

        // Assert
        $this->assertInstanceOf(TelegramaController::class, $controller);
    }

    /** @test */
    public function test_store_creates_telegrama_with_transaction(): void
    {
        // Arrange
        $mesa = Mesa::factory()->create(['electores' => 1000]);
        $lista = Lista::factory()->create();

        $data = [
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 100,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ];

        // Act
        $response = $this->postJson('/api/v1/telegramas', $data);

        // Assert
        $response->assertStatus(201)
                 ->assertJsonStructure(['id', 'mesa', 'lista']);

        $this->assertDatabaseHas('telegramas', [
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100
        ]);
    }

    /** @test */
    public function test_store_logs_audit_record(): void
    {
        // Arrange
        $mesa = Mesa::factory()->create(['electores' => 1000]);
        $lista = Lista::factory()->create();

        $data = [
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 100,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ];

        // Act
        $this->postJson('/api/v1/telegramas', $data);

        // Assert
        $this->assertDatabaseHas('auditoria', [
            'tabla' => 'telegramas',
            'accion' => 'CREATE',
            'usuario' => 'test_user'
        ]);
    }

    /** @test */
    public function test_store_rolls_back_on_exception(): void
    {
        // Arrange - Create invalid scenario (duplicated mesa+lista)
        $mesa = Mesa::factory()->create();
        $lista = Lista::factory()->create();
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id
        ]);

        $duplicateData = [
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 100,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ];

        // Act
        $response = $this->postJson('/api/v1/telegramas', $duplicateData);

        // Assert - Should return 422 and not create duplicate
        $response->assertStatus(422);
        $this->assertEquals(1, Telegrama::where('mesa_id', $mesa->id)->count());
    }
}
