<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreListaRequest;
use App\Http\Requests\UpdateListaRequest;
use App\Models\Lista;
use App\Models\Provincia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ListaRequestTest extends TestCase
{
    use RefreshDatabase;

    private Provincia $provincia;
    private Lista $listaDip;
    private Lista $listaSen;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos comunes para los tests
        $this->provincia = Provincia::create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $this->listaDip = Lista::create([
            'nombre' => 'Lista Diputados 1',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $this->listaSen = Lista::create([
            'nombre' => 'Lista Senadores 1',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);
    }

    // ===== Tests para StoreListaRequest =====

    /** @test */
    public function test_store_request_con_datos_validos_pasa_validacion()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datosValidos = [
            'nombre' => 'Lista Test',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_con_alianza_opcional_pasa()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datosValidos = [
            'nombre' => 'Lista Test',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'alianza' => 'Alianza Test'
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_sin_alianza_pasa()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datosValidos = [
            'nombre' => 'Lista Test',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'alianza' => null
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_nombre_requerido_falla()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datos = [
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
            // Falta nombre
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_store_request_provincia_id_requerido_falla()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datos = [
            'nombre' => 'Lista Test',
            'cargo' => Lista::CARGO_DIPUTADOS
            // Falta provincia_id
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('provincia_id'));
    }

    /** @test */
    public function test_store_request_cargo_requerido_falla()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datos = [
            'nombre' => 'Lista Test',
            'provincia_id' => $this->provincia->id
            // Falta cargo
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('cargo'));
    }

    /** @test */
    public function test_store_request_cargo_invalido_falla()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datos = [
            'nombre' => 'Lista Test',
            'provincia_id' => $this->provincia->id,
            'cargo' => 'PRESIDENTE' // Cargo inválido
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('cargo'));
    }

    /** @test */
    public function test_store_request_provincia_inexistente_falla()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datos = [
            'nombre' => 'Lista Test',
            'provincia_id' => 99999, // ID que no existe
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('provincia_id'));
    }

    /** @test */
    public function test_store_request_nombre_duplicado_misma_provincia_mismo_cargo_falla()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datos = [
            'nombre' => 'Lista Diputados 1', // Ya existe en esta provincia con DIPUTADOS
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];
        $request->merge($datos);

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_store_request_nombre_duplicado_misma_provincia_diferente_cargo_pasa()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datos = [
            'nombre' => 'Lista Senadores 1', // Existe con SENADORES, probamos con DIPUTADOS
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_nombre_duplicado_diferente_provincia_mismo_cargo_pasa()
    {
        // Arrange: Crear otra provincia
        $otraProvincia = Provincia::create([
            'nombre' => 'Córdoba',
            'codigo' => 'CB'
        ]);

        $request = new StoreListaRequest();
        $datos = [
            'nombre' => 'Lista Diputados 1', // Existe en BA, probamos en CB
            'provincia_id' => $otraProvincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_nombre_max_100_caracteres()
    {
        // Arrange
        $request = new StoreListaRequest();
        $nombreLargo = str_repeat('a', 101);
        $datos = [
            'nombre' => $nombreLargo,
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_store_request_alianza_max_100_caracteres()
    {
        // Arrange
        $request = new StoreListaRequest();
        $alianzaLarga = str_repeat('a', 101);
        $datos = [
            'nombre' => 'Lista Test',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'alianza' => $alianzaLarga
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('alianza'));
    }

    // ===== Tests para UpdateListaRequest =====

    /** @test */
    public function test_update_request_mismo_registro_no_falla_unique()
    {
        // Arrange
        $request = new UpdateListaRequest();
        $request->setRouteResolver(function () {
            return new class($this->listaDip->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosActualizados = [
            'nombre' => 'Lista Diputados 1', // Mismo nombre (debería pasar)
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $validator = Validator::make($datosActualizados, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_cambio_a_nombre_duplicado_misma_provincia_mismo_cargo_falla()
    {
        // Arrange: Crear otra lista
        $otraLista = Lista::create([
            'nombre' => 'Lista Diputados 2',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $request = new UpdateListaRequest();
        $request->setRouteResolver(function () {
            return new class($this->listaDip->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosConflicto = [
            'nombre' => 'Lista Diputados 2', // Ya existe en esta provincia+cargo
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];
        $request->merge($datosConflicto);

        // Act
        $validator = Validator::make($datosConflicto, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_update_request_cambio_provincia_pasa_validacion()
    {
        // Arrange: Crear otra provincia
        $otraProvincia = Provincia::create([
            'nombre' => 'Córdoba',
            'codigo' => 'CB'
        ]);

        $request = new UpdateListaRequest();
        $request->setRouteResolver(function () {
            return new class($this->listaDip->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosValidos = [
            'nombre' => 'Lista Diputados 1',
            'provincia_id' => $otraProvincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_cambio_cargo_pasa_validacion()
    {
        // Arrange
        $request = new UpdateListaRequest();
        $request->setRouteResolver(function () {
            return new class($this->listaDip->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosValidos = [
            'nombre' => 'Lista Test',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_campos_opcionales_sometimes()
    {
        // Arrange
        $request = new UpdateListaRequest();
        $request->setRouteResolver(function () {
            return new class($this->listaDip->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosParciales = [
            // Solo actualizamos el nombre
            'nombre' => 'Lista Actualizada'
        ];

        // Act
        $validator = Validator::make($datosParciales, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_datos_validos_pasan()
    {
        // Arrange
        $request = new UpdateListaRequest();
        $request->setRouteResolver(function () {
            return new class($this->listaDip->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosValidos = [
            'nombre' => 'Lista Actualizada',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'alianza' => 'Nueva Alianza'
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_mensajes_error_en_espanol()
    {
        // Arrange
        $request = new StoreListaRequest();
        $datos = [
            'cargo' => 'INVALIDO'
        ];

        // Act
        $validator = Validator::make($datos, $request->rules(), $request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals('El cargo debe ser DIPUTADOS o SENADORES', $validator->errors()->first('cargo'));
    }
}
