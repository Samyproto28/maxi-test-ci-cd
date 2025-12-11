<?php

namespace Tests\Unit\Services;

use App\Models\Mesa;
use App\Models\Telegrama;
use App\Models\Lista;
use App\Models\Provincia;
use App\Services\TelegramaValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramaValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TelegramaValidationService $validationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validationService = app(TelegramaValidationService::class);
    }

    /** @test */
    public function test_valida_suma_votos_dentro_limite()
    {
        // Arrange: Crear provincia, mesa y lista
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA-001',
            'provincia_id' => $provincia->id,
            'electores' => 1000
        ]);
        $lista = Lista::create([
            'nombre' => 'Lista A',
            'provincia_id' => $provincia->id,
            'cargo' => 'DIPUTADOS'
        ]);

        // Crear telegrama existente con 300 votos
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 100,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ]);

        // Act: Validar nuevo telegrama con 600 votos (total: 900 < 1000)
        $datosNuevos = [
            'votos_diputados' => 200,
            'votos_senadores' => 200,
            'blancos' => 100,
            'nulos' => 50,
            'recurridos' => 50
        ];

        $resultado = $this->validationService->validarSumaVotosNoExcedeElectores($mesa->id, $datosNuevos);

        // Assert
        $this->assertTrue($resultado);
    }

    /** @test */
    public function test_lanza_excepcion_suma_excede_electores()
    {
        // Arrange
        $provincia = Provincia::create(['nombre' => 'Córdoba', 'codigo' => 'CB']);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA-002',
            'provincia_id' => $provincia->id,
            'electores' => 1000
        ]);
        $lista = Lista::create([
            'nombre' => 'Lista B',
            'provincia_id' => $provincia->id,
            'cargo' => 'SENADORES'
        ]);

        // Crear telegrama existente con 700 votos
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 200,
            'votos_senadores' => 300,
            'blancos' => 100,
            'nulos' => 60,
            'recurridos' => 40,
            'usuario' => 'test_user'
        ]);

        // Act & Assert: Intentar agregar 400 votos (total: 1100 > 1000)
        $datosNuevos = [
            'votos_diputados' => 150,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La suma de votos (1100) excede la cantidad de electores (1000) de la mesa MESA-002');

        $this->validationService->validarSumaVotosNoExcedeElectores($mesa->id, $datosNuevos);
    }

    /** @test */
    public function test_excluye_telegrama_actual_al_actualizar()
    {
        // Arrange
        $provincia = Provincia::create(['nombre' => 'Santa Fe', 'codigo' => 'SF']);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA-003',
            'provincia_id' => $provincia->id,
            'electores' => 1000
        ]);
        $lista = Lista::create([
            'nombre' => 'Lista C',
            'provincia_id' => $provincia->id,
            'cargo' => 'DIPUTADOS'
        ]);

        // Crear telegrama con 600 votos
        $telegrama = Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 200,
            'votos_senadores' => 200,
            'blancos' => 100,
            'nulos' => 60,
            'recurridos' => 40,
            'usuario' => 'test_user'
        ]);

        // Act: Actualizar mismo telegrama con 900 votos (excluye los 600 anteriores)
        $datosActualizados = [
            'votos_diputados' => 300,
            'votos_senadores' => 300,
            'blancos' => 150,
            'nulos' => 90,
            'recurridos' => 60
        ];

        $resultado = $this->validationService->validarSumaVotosNoExcedeElectores(
            $mesa->id,
            $datosActualizados,
            $telegrama->id
        );

        // Assert: Solo cuenta los 900 nuevos, no los 600 anteriores
        $this->assertTrue($resultado);
    }

    /** @test */
    public function test_calcula_correctamente_con_multiples_telegramas()
    {
        // Arrange
        $provincia = Provincia::create(['nombre' => 'Mendoza', 'codigo' => 'MZ']);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA-004',
            'provincia_id' => $provincia->id,
            'electores' => 2000
        ]);

        // Crear 3 listas
        $lista1 = Lista::create(['nombre' => 'Lista 1', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $lista2 = Lista::create(['nombre' => 'Lista 2', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $lista3 = Lista::create(['nombre' => 'Lista 3', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);

        // Crear 3 telegramas con 300, 400, 500 votos respectivamente
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 100,
            'votos_senadores' => 100,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ]);
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 150,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ]);
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista3->id,
            'votos_diputados' => 200,
            'votos_senadores' => 150,
            'blancos' => 100,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ]);

        // Act: Validar nuevo telegrama con 700 votos (total: 1900 < 2000)
        $lista4 = Lista::create(['nombre' => 'Lista 4', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $datosNuevos = [
            'votos_diputados' => 250,
            'votos_senadores' => 250,
            'blancos' => 100,
            'nulos' => 60,
            'recurridos' => 40
        ];

        $resultado = $this->validationService->validarSumaVotosNoExcedeElectores($mesa->id, $datosNuevos);
        $this->assertTrue($resultado);

        // Crear el 4to telegrama en la base de datos
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista4->id,
            'votos_diputados' => 250,
            'votos_senadores' => 250,
            'blancos' => 100,
            'nulos' => 60,
            'recurridos' => 40,
            'usuario' => 'test_user'
        ]);

        // Act & Assert: Intentar agregar otro con 200 votos (total: 2100 > 2000)
        $lista5 = Lista::create(['nombre' => 'Lista 5', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $datosExceso = [
            'votos_diputados' => 80,
            'votos_senadores' => 60,
            'blancos' => 30,
            'nulos' => 20,
            'recurridos' => 10
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->validationService->validarSumaVotosNoExcedeElectores($mesa->id, $datosExceso);
    }

    /** @test */
    public function test_valida_votos_no_negativos_pasa()
    {
        // Arrange: Datos con todos los valores positivos
        $datosPositivos = [
            'votos_diputados' => 100,
            'votos_senadores' => 200,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20
        ];

        // Act & Assert
        $resultado = $this->validationService->validarVotosNoNegativos($datosPositivos);
        $this->assertTrue($resultado);

        // Arrange: Datos con valores en cero (válido)
        $datosCeros = [
            'votos_diputados' => 0,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ];

        // Act & Assert
        $resultado = $this->validationService->validarVotosNoNegativos($datosCeros);
        $this->assertTrue($resultado);
    }

    /** @test */
    public function test_votos_negativos_lanza_excepcion()
    {
        // Test para votos_diputados negativo
        $datos = ['votos_diputados' => -10];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El campo votos_diputados no puede ser negativo');
        $this->validationService->validarVotosNoNegativos($datos);
    }

    /** @test */
    public function test_votos_senadores_negativos_lanza_excepcion()
    {
        $datos = ['votos_senadores' => -5];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El campo votos_senadores no puede ser negativo');
        $this->validationService->validarVotosNoNegativos($datos);
    }

    /** @test */
    public function test_blancos_negativos_lanza_excepcion()
    {
        $datos = ['blancos' => -1];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El campo blancos no puede ser negativo');
        $this->validationService->validarVotosNoNegativos($datos);
    }

    /** @test */
    public function test_nulos_negativos_lanza_excepcion()
    {
        $datos = ['nulos' => -3];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El campo nulos no puede ser negativo');
        $this->validationService->validarVotosNoNegativos($datos);
    }

    /** @test */
    public function test_recurridos_negativos_lanza_excepcion()
    {
        $datos = ['recurridos' => -2];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El campo recurridos no puede ser negativo');
        $this->validationService->validarVotosNoNegativos($datos);
    }

    /** @test */
    public function test_maneja_campos_ausentes_en_array()
    {
        // Arrange: Array con solo algunos campos
        $datosParciales = [
            'votos_diputados' => 100,
            'blancos' => 50
        ];

        // Act & Assert: Debería pasar (campos ausentes se tratan como válidos)
        $resultado = $this->validationService->validarVotosNoNegativos($datosParciales);
        $this->assertTrue($resultado);

        // Arrange: Array vacío
        $datosVacios = [];

        // Act & Assert: Debería pasar
        $resultado = $this->validationService->validarVotosNoNegativos($datosVacios);
        $this->assertTrue($resultado);
    }
}
