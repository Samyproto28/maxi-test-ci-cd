<?php

namespace Tests\Feature;

use App\Models\Candidato;
use App\Models\Lista;
use App\Models\Mesa;
use App\Models\Provincia;
use App\Models\Telegrama;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultadoControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_provincial_endpoint_retorna_resultados_correctos()
    {
        // Arrange
        $provincia = Provincia::factory()->create(['nombre' => 'Buenos Aires']);

        $lista1 = Lista::factory()->create([
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $lista2 = Lista::factory()->create([
            'nombre' => 'Lista B',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);

        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 750,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 250,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $response = $this->getJson("/api/v1/resultados/provincial/{$provincia->id}?cargo=DIPUTADOS");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'provincia_id' => $provincia->id,
                'cargo' => Lista::CARGO_DIPUTADOS,
                'total_votos_validos' => 1000,
            ])
            ->assertJsonStructure([
                'provincia_id',
                'cargo',
                'total_votos_validos',
                'listas' => [
                    '*' => [
                        'id',
                        'nombre',
                        'votos',
                        'porcentaje'
                    ]
                ]
            ]);

        $data = $response->json();
        $this->assertEquals(75.0, $data['listas'][0]['porcentaje']);
        $this->assertEquals(25.0, $data['listas'][1]['porcentaje']);
    }

    /** @test */
    public function test_provincial_endpoint_valida_parametro_cargo_requerido()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/resultados/provincial/{$provincia->id}");

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }

    /** @test */
    public function test_provincial_endpoint_valida_cargo_invalido()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/resultados/provincial/{$provincia->id}?cargo=INVALID");

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }

    /** @test */
    public function test_provincial_endpoint_acepta_diputados_y_senadores()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        $listaDiputados = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $listaSenadores = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);

        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $listaDiputados->id,
            'votos_diputados' => 100,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $listaSenadores->id,
            'votos_diputados' => 0,
            'votos_senadores' => 200,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act & Assert - DIPUTADOS
        $responseDiputados = $this->getJson("/api/v1/resultados/provincial/{$provincia->id}?cargo=DIPUTADOS");
        $responseDiputados->assertStatus(200)
            ->assertJson(['cargo' => Lista::CARGO_DIPUTADOS]);

        // Act & Assert - SENADORES
        $responseSenadores = $this->getJson("/api/v1/resultados/provincial/{$provincia->id}?cargo=SENADORES");
        $responseSenadores->assertStatus(200)
            ->assertJson(['cargo' => Lista::CARGO_SENADORES]);
    }

    /** @test */
    public function test_nacional_endpoint_agrega_resultados_de_todas_provincias()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create(['nombre' => 'Buenos Aires']);
        $provincia2 = Provincia::factory()->create(['nombre' => 'Córdoba']);

        $lista1 = Lista::factory()->create([
            'nombre' => 'Lista Nacional A',
            'alianza' => 'Unión Nacional',
            'provincia_id' => $provincia1->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        $lista2 = Lista::factory()->create([
            'nombre' => 'Lista Nacional A',
            'alianza' => 'Unión Nacional',
            'provincia_id' => $provincia2->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        $mesa1 = Mesa::factory()->create(['provincia_id' => $provincia1->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista1->id,
            'votos_senadores' => 1000,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa2 = Mesa::factory()->create(['provincia_id' => $provincia2->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista2->id,
            'votos_senadores' => 2000,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $response = $this->getJson('/api/v1/resultados/nacional?cargo=SENADORES');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'cargo' => Lista::CARGO_SENADORES,
                'total_votos_validos' => 3000,
            ])
            ->assertJsonStructure([
                'cargo',
                'total_votos_validos',
                'listas' => [
                    '*' => [
                        'nombre',
                        'alianza',
                        'votos',
                        'porcentaje'
                    ]
                ]
            ]);
    }

    /** @test */
    public function test_nacional_endpoint_valida_parametro_cargo()
    {
        // Act
        $response = $this->getJson('/api/v1/resultados/nacional');

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }

    /** @test */
    public function test_nacional_endpoint_ordena_resultados_por_votos_descendente()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();

        $lista1 = Lista::factory()->create([
            'nombre' => 'Lista X',
            'provincia_id' => $provincia1->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $lista2 = Lista::factory()->create([
            'nombre' => 'Lista Y',
            'provincia_id' => $provincia2->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $mesa1 = Mesa::factory()->create(['provincia_id' => $provincia1->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 300,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa2 = Mesa::factory()->create(['provincia_id' => $provincia2->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 700,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $response = $this->getJson('/api/v1/resultados/nacional?cargo=DIPUTADOS');

        // Assert
        $response->assertStatus(200);
        $data = $response->json();

        // Should be ordered: Lista Y (700), Lista X (300)
        $this->assertEquals('Lista Y', $data['listas'][0]['nombre']);
        $this->assertEquals(700, $data['listas'][0]['votos']);
        $this->assertEquals('Lista X', $data['listas'][1]['nombre']);
        $this->assertEquals(300, $data['listas'][1]['votos']);
    }

    /** @test */
    public function test_por_candidato_endpoint_retorna_datos_del_candidato()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        $lista = Lista::factory()->create([
            'nombre' => 'Lista Completa',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $candidato = Candidato::create([
            'nombre' => 'Juan Pérez',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 1
        ]);

        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 500,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $response = $this->getJson("/api/v1/resultados/candidato/{$candidato->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'candidato_id' => $candidato->id,
                'candidato_nombre' => 'Juan Pérez',
                'lista_id' => $lista->id,
                'lista_nombre' => 'Lista Completa',
                'cargo' => Lista::CARGO_DIPUTADOS,
                'votos_lista' => 500,
            ])
            ->assertJsonStructure([
                'candidato_id',
                'candidato_nombre',
                'lista_id',
                'lista_nombre',
                'cargo',
                'votos_lista',
                'provincia_id'
            ]);
    }

    /** @test */
    public function test_por_candidato_endpoint_retorna_404_con_id_invalido()
    {
        // Act
        $response = $this->getJson('/api/v1/resultados/candidato/99999');

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function test_por_lista_endpoint_retorna_datos_de_lista()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        $lista = Lista::factory()->create([
            'nombre' => 'Lista Nacional',
            'alianza' => 'Gran Alianza',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        $candidato1 = Candidato::create([
            'nombre' => 'Primer Candidato',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES,
            'orden' => 1
        ]);

        $candidato2 = Candidato::create([
            'nombre' => 'Segundo Candidato',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES,
            'orden' => 2
        ]);

        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_senadores' => 350,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $response = $this->getJson("/api/v1/resultados/lista/{$lista->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'lista_id' => $lista->id,
                'lista_nombre' => 'Lista Nacional',
                'lista_alianza' => 'Gran Alianza',
                'cargo' => Lista::CARGO_SENADORES,
                'provincia_id' => $provincia->id,
                'total_votos' => 350,
            ])
            ->assertJsonStructure([
                'lista_id',
                'lista_nombre',
                'lista_alianza',
                'cargo',
                'provincia_id',
                'total_votos',
                'candidatos' => [
                    '*' => [
                        'id',
                        'nombre',
                        'orden'
                    ]
                ]
            ]);

        $data = $response->json();
        $this->assertCount(2, $data['candidatos']);
        $this->assertEquals('Primer Candidato', $data['candidatos'][0]['nombre']);
        $this->assertEquals('Segundo Candidato', $data['candidatos'][1]['nombre']);
    }

    /** @test */
    public function test_todas_las_rutas_estan_registradas()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);
        $candidato = Candidato::create([
            'nombre' => 'Test',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 1
        ]);

        // Act & Assert - Verify all routes exist
        $this->getJson("/api/v1/resultados/provincial/{$provincia->id}?cargo=DIPUTADOS")
            ->assertStatus(200);

        $this->getJson('/api/v1/resultados/nacional?cargo=DIPUTADOS')
            ->assertStatus(200);

        $this->getJson("/api/v1/resultados/candidato/{$candidato->id}")
            ->assertStatus(200);

        $this->getJson("/api/v1/resultados/lista/{$lista->id}")
            ->assertStatus(200);
    }
}
