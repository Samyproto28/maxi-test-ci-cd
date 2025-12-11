<?php

namespace Tests\Unit\Services;

use App\Models\Provincia;
use App\Models\Lista;
use App\Models\Mesa;
use App\Models\Telegrama;
use App\Models\Candidato;
use App\Services\ResultadoCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultadoCalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResultadoCalculationService $calculationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculationService = app(ResultadoCalculationService::class);
    }

    /** @test */
    public function test_service_can_be_instantiated()
    {
        // Act
        $service = app(ResultadoCalculationService::class);

        // Assert
        $this->assertInstanceOf(ResultadoCalculationService::class, $service);
    }

    /** @test */
    public function test_resultados_por_provincia_calcula_votos_correctamente()
    {
        // Arrange
        $provincia = Provincia::factory()->create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $lista1 = Lista::factory()->create([
            'nombre' => 'Lista A',
            'alianza' => 'Alianza Norte',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $lista2 = Lista::factory()->create([
            'nombre' => 'Lista B',
            'alianza' => 'Alianza Sur',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Mesa 1: Lista A = 150, Lista B = 100
        $mesa1 = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 150,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 100,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Mesa 2: Lista A = 100, Lista B = 150
        $mesa2 = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 100,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 150,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $resultado = $this->calculationService->resultadosPorProvincia(
            $provincia->id,
            Lista::CARGO_DIPUTADOS
        );

        // Assert
        $this->assertEquals($provincia->id, $resultado['provincia_id']);
        $this->assertEquals(Lista::CARGO_DIPUTADOS, $resultado['cargo']);
        $this->assertEquals(500, $resultado['total_votos_validos']); // 250 + 250

        // Find Lista A in results
        $listaAResult = collect($resultado['listas'])->firstWhere('id', $lista1->id);
        $this->assertEquals(250, $listaAResult['votos']); // 150 + 100

        // Find Lista B in results
        $listaBResult = collect($resultado['listas'])->firstWhere('id', $lista2->id);
        $this->assertEquals(250, $listaBResult['votos']); // 100 + 150
    }

    /** @test */
    public function test_resultados_por_provincia_calcula_porcentajes_correctamente()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista1 = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);
        $lista2 = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);

        // Lista 1: 750 votos (75%)
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 750,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Lista 2: 250 votos (25%)
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
        $resultado = $this->calculationService->resultadosPorProvincia(
            $provincia->id,
            Lista::CARGO_DIPUTADOS
        );

        // Assert
        $lista1Result = collect($resultado['listas'])->firstWhere('id', $lista1->id);
        $lista2Result = collect($resultado['listas'])->firstWhere('id', $lista2->id);

        $this->assertEquals(75.0, $lista1Result['porcentaje']);
        $this->assertEquals(25.0, $lista2Result['porcentaje']);

        // Total percentages should sum to ~100
        $totalPorcentaje = $lista1Result['porcentaje'] + $lista2Result['porcentaje'];
        $this->assertEqualsWithDelta(100.0, $totalPorcentaje, 0.01);
    }

    /** @test */
    public function test_resultados_por_provincia_ordena_por_votos_descendente()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        $lista1 = Lista::factory()->create([
            'nombre' => 'Lista C',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);
        $lista2 = Lista::factory()->create([
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);
        $lista3 = Lista::factory()->create([
            'nombre' => 'Lista B',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);

        // Create with different vote counts: 100, 300, 200
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista1->id,
            'votos_senadores' => 100,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista2->id,
            'votos_senadores' => 300,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista3->id,
            'votos_senadores' => 200,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $resultado = $this->calculationService->resultadosPorProvincia(
            $provincia->id,
            Lista::CARGO_SENADORES
        );

        // Assert - Should be ordered: 300, 200, 100
        $listas = $resultado['listas'];
        $this->assertEquals($lista2->id, $listas[0]['id']); // 300 votes
        $this->assertEquals($lista3->id, $listas[1]['id']); // 200 votes
        $this->assertEquals($lista1->id, $listas[2]['id']); // 100 votes

        $this->assertEquals(300, $listas[0]['votos']);
        $this->assertEquals(200, $listas[1]['votos']);
        $this->assertEquals(100, $listas[2]['votos']);
    }

    /** @test */
    public function test_resultados_por_provincia_maneja_provincia_sin_telegramas()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        // No telegramas created

        // Act
        $resultado = $this->calculationService->resultadosPorProvincia(
            $provincia->id,
            Lista::CARGO_DIPUTADOS
        );

        // Assert
        $this->assertEquals($provincia->id, $resultado['provincia_id']);
        $this->assertEquals(Lista::CARGO_DIPUTADOS, $resultado['cargo']);
        $this->assertEquals(0, $resultado['total_votos_validos']);
        $this->assertIsArray($resultado['listas']);
        $this->assertEmpty($resultado['listas']);
    }

    /** @test */
    public function test_resultados_por_provincia_filtra_por_cargo()
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

        // Act - Request DIPUTADOS only
        $resultadoDiputados = $this->calculationService->resultadosPorProvincia(
            $provincia->id,
            Lista::CARGO_DIPUTADOS
        );

        // Assert
        $this->assertEquals(100, $resultadoDiputados['total_votos_validos']);
        $this->assertCount(1, $resultadoDiputados['listas']);
        $this->assertEquals($listaDiputados->id, $resultadoDiputados['listas'][0]['id']);

        // Act - Request SENADORES only
        $resultadoSenadores = $this->calculationService->resultadosPorProvincia(
            $provincia->id,
            Lista::CARGO_SENADORES
        );

        // Assert
        $this->assertEquals(200, $resultadoSenadores['total_votos_validos']);
        $this->assertCount(1, $resultadoSenadores['listas']);
        $this->assertEquals($listaSenadores->id, $resultadoSenadores['listas'][0]['id']);
    }

    /** @test */
    public function test_resumen_nacional_agrega_todas_provincias()
    {
        // Arrange - Create 3 provinces
        $provincia1 = Provincia::factory()->create(['nombre' => 'Buenos Aires']);
        $provincia2 = Provincia::factory()->create(['nombre' => 'Córdoba']);
        $provincia3 = Provincia::factory()->create(['nombre' => 'Santa Fe']);

        $lista1 = Lista::factory()->create([
            'nombre' => 'Lista Nacional A',
            'alianza' => 'Unión Nacional',
            'provincia_id' => $provincia1->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $lista2 = Lista::factory()->create([
            'nombre' => 'Lista Nacional A',
            'alianza' => 'Unión Nacional',
            'provincia_id' => $provincia2->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $lista3 = Lista::factory()->create([
            'nombre' => 'Lista Nacional A',
            'alianza' => 'Unión Nacional',
            'provincia_id' => $provincia3->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Create mesas and telegramas
        $mesa1 = Mesa::factory()->create(['provincia_id' => $provincia1->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 1000,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa2 = Mesa::factory()->create(['provincia_id' => $provincia2->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 2000,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa3 = Mesa::factory()->create(['provincia_id' => $provincia3->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa3->id,
            'lista_id' => $lista3->id,
            'votos_diputados' => 1500,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $resultado = $this->calculationService->resumenNacional(Lista::CARGO_DIPUTADOS);

        // Assert
        $this->assertEquals(Lista::CARGO_DIPUTADOS, $resultado['cargo']);
        $this->assertEquals(4500, $resultado['total_votos_validos']); // 1000 + 2000 + 1500

        // Should aggregate by lista name
        $listaResult = collect($resultado['listas'])
            ->firstWhere('nombre', 'Lista Nacional A');
        $this->assertEquals(4500, $listaResult['votos']);
    }

    /** @test */
    public function test_resumen_nacional_distingue_listas_por_nombre_y_alianza()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();

        // Two different listas with same name but different alianza
        $lista1 = Lista::factory()->create([
            'nombre' => 'Lista A',
            'alianza' => 'Frente Norte',
            'provincia_id' => $provincia1->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        $lista2 = Lista::factory()->create([
            'nombre' => 'Lista A',
            'alianza' => 'Frente Sur',
            'provincia_id' => $provincia2->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        $mesa1 = Mesa::factory()->create(['provincia_id' => $provincia1->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista1->id,
            'votos_senadores' => 500,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa2 = Mesa::factory()->create(['provincia_id' => $provincia2->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista2->id,
            'votos_senadores' => 300,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $resultado = $this->calculationService->resumenNacional(Lista::CARGO_SENADORES);

        // Assert
        $this->assertEquals(800, $resultado['total_votos_validos']);
        $this->assertCount(2, $resultado['listas']); // Should be 2 separate entries

        $frenteNorte = collect($resultado['listas'])
            ->firstWhere('alianza', 'Frente Norte');
        $frenteSur = collect($resultado['listas'])
            ->firstWhere('alianza', 'Frente Sur');

        $this->assertEquals(500, $frenteNorte['votos']);
        $this->assertEquals(300, $frenteSur['votos']);
    }

    /** @test */
    public function test_resumen_nacional_calcula_porcentajes_y_ordena()
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

        // Lista X: 700 votes, Lista Y: 300 votes
        $mesa1 = Mesa::factory()->create(['provincia_id' => $provincia1->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 700,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa2 = Mesa::factory()->create(['provincia_id' => $provincia2->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 300,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $resultado = $this->calculationService->resumenNacional(Lista::CARGO_DIPUTADOS);

        // Assert - Ordered by votes descending
        $this->assertEquals('Lista X', $resultado['listas'][0]['nombre']);
        $this->assertEquals('Lista Y', $resultado['listas'][1]['nombre']);

        $this->assertEquals(700, $resultado['listas'][0]['votos']);
        $this->assertEquals(300, $resultado['listas'][1]['votos']);

        $this->assertEquals(70.0, $resultado['listas'][0]['porcentaje']);
        $this->assertEquals(30.0, $resultado['listas'][1]['porcentaje']);

        // Percentages sum to 100
        $totalPorcentaje = $resultado['listas'][0]['porcentaje'] + 
                           $resultado['listas'][1]['porcentaje'];
        $this->assertEqualsWithDelta(100.0, $totalPorcentaje, 0.01);
    }

    /** @test */
    public function test_resumen_nacional_maneja_sin_datos()
    {
        // Arrange - No data created

        // Act
        $resultado = $this->calculationService->resumenNacional(Lista::CARGO_DIPUTADOS);

        // Assert
        $this->assertEquals(Lista::CARGO_DIPUTADOS, $resultado['cargo']);
        $this->assertEquals(0, $resultado['total_votos_validos']);
        $this->assertEmpty($resultado['listas']);
    }

    /** @test */
    public function test_resultados_por_candidato_incluye_votos_de_su_lista()
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

        // Create telegramas for the lista
        $mesa1 = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 500,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa2 = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 300,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $resultado = $this->calculationService->resultadosPorCandidato($candidato->id);

        // Assert
        $this->assertEquals($candidato->id, $resultado['candidato_id']);
        $this->assertEquals('Juan Pérez', $resultado['candidato_nombre']);
        $this->assertEquals($lista->id, $resultado['lista_id']);
        $this->assertEquals('Lista Completa', $resultado['lista_nombre']);
        $this->assertEquals(Lista::CARGO_DIPUTADOS, $resultado['cargo']);
        $this->assertEquals(800, $resultado['votos_lista']); // 500 + 300
        $this->assertEquals($provincia->id, $resultado['provincia_id']);
    }

    /** @test */
    public function test_resultados_por_candidato_lanza_excepcion_si_no_existe()
    {
        // Arrange - No candidato created

        // Act & Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->calculationService->resultadosPorCandidato(99999);
    }

    /** @test */
    public function test_resultados_por_lista_agrega_votos_correctamente()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        $lista = Lista::factory()->create([
            'nombre' => 'Lista Nacional',
            'alianza' => 'Gran Alianza',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        // Create multiple telegramas
        $mesa1 = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista->id,
            'votos_senadores' => 150,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa2 = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista->id,
            'votos_senadores' => 250,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        $mesa3 = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa3->id,
            'lista_id' => $lista->id,
            'votos_senadores' => 100,
            'votos_diputados' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $resultado = $this->calculationService->resultadosPorLista($lista->id);

        // Assert
        $this->assertEquals($lista->id, $resultado['lista_id']);
        $this->assertEquals('Lista Nacional', $resultado['lista_nombre']);
        $this->assertEquals('Gran Alianza', $resultado['lista_alianza']);
        $this->assertEquals(Lista::CARGO_SENADORES, $resultado['cargo']);
        $this->assertEquals($provincia->id, $resultado['provincia_id']);
        $this->assertEquals(500, $resultado['total_votos']); // 150 + 250 + 100
    }

    /** @test */
    public function test_resultados_por_lista_incluye_candidatos()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Create candidatos in specific order
        $candidato1 = Candidato::create([
            'nombre' => 'Primer Candidato',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 1
        ]);

        $candidato2 = Candidato::create([
            'nombre' => 'Segundo Candidato',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 2
        ]);

        $candidato3 = Candidato::create([
            'nombre' => 'Tercer Candidato',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 3
        ]);

        // Create at least one telegrama
        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $resultado = $this->calculationService->resultadosPorLista($lista->id);

        // Assert - Should include candidatos array ordered by 'orden'
        $this->assertArrayHasKey('candidatos', $resultado);
        $this->assertCount(3, $resultado['candidatos']);

        $this->assertEquals($candidato1->id, $resultado['candidatos'][0]['id']);
        $this->assertEquals('Primer Candidato', $resultado['candidatos'][0]['nombre']);
        $this->assertEquals(1, $resultado['candidatos'][0]['orden']);

        $this->assertEquals($candidato2->id, $resultado['candidatos'][1]['id']);
        $this->assertEquals(2, $resultado['candidatos'][1]['orden']);

        $this->assertEquals($candidato3->id, $resultado['candidatos'][2]['id']);
        $this->assertEquals(3, $resultado['candidatos'][2]['orden']);
    }

    /** @test */
    public function test_resultados_por_provincia_usa_cache()
    {
        // Arrange
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create([
            'nombre' => 'Lista Test',
            'provincia_id' => $provincia->id,
            'cargo' => 'DIPUTADOS',
            'alianza' => 'Test Alianza'
        ]);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA-CACHE-1',
            'provincia_id' => $provincia->id,
            'electores' => 1000
        ]);
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 500,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0,
            'usuario' => 'test_user'
        ]);

        // Act - Call twice to test cache mechanism
        $resultado1 = $this->calculationService->resultadosPorProvincia($provincia->id, 'DIPUTADOS');
        $resultado2 = $this->calculationService->resultadosPorProvincia($provincia->id, 'DIPUTADOS');

        // Assert - Results should be identical (cache working correctly)
        $this->assertEquals($resultado1, $resultado2);
        $this->assertEquals(500, $resultado1['total_votos_validos']);
        $this->assertCount(1, $resultado1['listas']);
    }

    /** @test */
    public function test_resumen_nacional_usa_cache()
    {
        // Arrange
        $provincia = Provincia::create(['nombre' => 'Córdoba', 'codigo' => 'CB']);
        $lista = Lista::create([
            'nombre' => 'Lista Nacional',
            'provincia_id' => $provincia->id,
            'cargo' => 'SENADORES',
            'alianza' => 'Alianza Nacional'
        ]);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA-CACHE-2',
            'provincia_id' => $provincia->id,
            'electores' => 1000
        ]);
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 0,
            'votos_senadores' => 300,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0,
            'usuario' => 'test_user'
        ]);

        // Act - Call twice to test cache mechanism
        $resultado1 = $this->calculationService->resumenNacional('SENADORES');
        $resultado2 = $this->calculationService->resumenNacional('SENADORES');

        // Assert - Results should be identical (cache working correctly)
        $this->assertEquals($resultado1, $resultado2);
        $this->assertEquals(300, $resultado1['total_votos_validos']);
        $this->assertCount(1, $resultado1['listas']);
    }

    /** @test */
    public function test_cache_se_invalida_al_crear_telegrama()
    {
        // Arrange
        $provincia = Provincia::create(['nombre' => 'Santa Fe', 'codigo' => 'SF']);
        $lista1 = Lista::create([
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => 'DIPUTADOS',
            'alianza' => 'Alianza A'
        ]);
        $lista2 = Lista::create([
            'nombre' => 'Lista B',
            'provincia_id' => $provincia->id,
            'cargo' => 'DIPUTADOS',
            'alianza' => 'Alianza B'
        ]);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA-CACHE-3',
            'provincia_id' => $provincia->id,
            'electores' => 1000
        ]);
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 100,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0,
            'usuario' => 'test_user'
        ]);

        // Act - Call resumenNacional to populate cache
        $resultadoAntes = $this->calculationService->resumenNacional('DIPUTADOS');
        $this->assertEquals(100, $resultadoAntes['total_votos_validos']);

        // Create new telegrama for different lista (should invalidate cache)
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 200,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0,
            'usuario' => 'test_user'
        ]);

        // Act - Call resumenNacional again (should reflect new data)
        $resultadoDespues = $this->calculationService->resumenNacional('DIPUTADOS');

        // Assert - New data should be reflected (cache was invalidated)
        $this->assertEquals(300, $resultadoDespues['total_votos_validos']);
    }
}
