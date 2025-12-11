<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreMesaRequest;
use App\Http\Requests\UpdateMesaRequest;
use App\Models\Mesa;
use App\Models\Provincia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MesaRequestTest extends TestCase
{
    use RefreshDatabase;

    private Provincia $provincia;
    private Mesa $mesa;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos comunes para los tests
        $this->provincia = Provincia::create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $this->mesa = Mesa::create([
            'id_mesa' => 'MESA-001',
            'provincia_id' => $this->provincia->id,
            'electores' => 1000
        ]);
    }

    // ===== Tests para StoreMesaRequest =====

    /** @test */
    public function test_store_request_con_datos_validos_pasa_validacion()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datosValidos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_con_circuito_opcional_pasa()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datosValidos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'circuito' => '001',
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_con_establecimiento_opcional_pasa()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datosValidos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'establecimiento' => 'Escuela Nacional',
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_sin_circuito_pasa()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datosValidos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'circuito' => null,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_sin_establecimiento_pasa()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datosValidos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'establecimiento' => null,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_id_mesa_requerido_falla()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'provincia_id' => $this->provincia->id,
            'electores' => 800
            // Falta id_mesa
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('id_mesa'));
    }

    /** @test */
    public function test_store_request_provincia_id_requerido_falla()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => 'MESA-002',
            'electores' => 800
            // Falta provincia_id
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('provincia_id'));
    }

    /** @test */
    public function test_store_request_electores_requeridos_falla()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id
            // Falta electores
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('electores'));
    }

    /** @test */
    public function test_store_request_id_mesa_duplicado_falla()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => 'MESA-001', // Ya existe
            'provincia_id' => $this->provincia->id,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('id_mesa'));
    }

    /** @test */
    public function test_store_request_id_mesa_duplicado_diferente_provincia_falla()
    {
        // Arrange: Crear otra provincia
        $otraProvincia = Provincia::create([
            'nombre' => 'Córdoba',
            'codigo' => 'CB'
        ]);

        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => 'MESA-001', // Mismo ID pero en provincia diferente
            'provincia_id' => $otraProvincia->id,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('id_mesa'));
    }

    /** @test */
    public function test_store_request_provincia_inexistente_falla()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => 99999, // ID que no existe
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('provincia_id'));
    }

    /** @test */
    public function test_store_request_electores_debe_ser_entero()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'electores' => '800.5' // Número decimal
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('electores'));
    }

    /** @test */
    public function test_store_request_electores_minimo_1()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'electores' => 0 // Menor a 1
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('electores'));
    }

    /** @test */
    public function test_store_request_id_mesa_max_20_caracteres()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $idMesaLargo = str_repeat('M', 21);
        $datos = [
            'id_mesa' => $idMesaLargo,
            'provincia_id' => $this->provincia->id,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('id_mesa'));
    }

    /** @test */
    public function test_store_request_circuito_max_50_caracteres()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $circuitoLargo = str_repeat('C', 51);
        $datos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'circuito' => $circuitoLargo,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('circuito'));
    }

    /** @test */
    public function test_store_request_establecimiento_max_200_caracteres()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $establecimientoLargo = str_repeat('E', 201);
        $datos = [
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'establecimiento' => $establecimientoLargo,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('establecimiento'));
    }

    /** @test */
    public function test_store_request_id_mesa_debe_ser_string()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => 123, // Número en lugar de string
            'provincia_id' => $this->provincia->id,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails()); // Debe fallar porque debe ser string
    }

    /** @test */
    public function test_store_request_id_mesa_string_pasa_validacion()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [
            'id_mesa' => '123', // String
            'provincia_id' => $this->provincia->id,
            'electores' => 800
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    // ===== Tests para UpdateMesaRequest =====

    /** @test */
    public function test_update_request_mismo_registro_no_falla_unique_id_mesa()
    {
        // Arrange
        $request = new UpdateMesaRequest();
        $request->setRouteResolver(function () {
            return new class($this->mesa->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosActualizados = [
            'id_mesa' => 'MESA-001', // Mismo id_mesa (debería pasar)
            'provincia_id' => $this->provincia->id,
            'electores' => 1200
        ];

        // Act
        $validator = Validator::make($datosActualizados, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_cambio_a_id_mesa_duplicado_falla()
    {
        // Arrange: Crear otra mesa
        $otraMesa = Mesa::create([
            'id_mesa' => 'MESA-002',
            'provincia_id' => $this->provincia->id,
            'electores' => 900
        ]);

        $request = new UpdateMesaRequest();
        $request->setRouteResolver(function () {
            return new class($this->mesa->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosConflicto = [
            'id_mesa' => 'MESA-002', // Ya existe en otra mesa
            'provincia_id' => $this->provincia->id,
            'electores' => 1200
        ];

        // Act
        $validator = Validator::make($datosConflicto, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('id_mesa'));
    }

    /** @test */
    public function test_update_request_datos_validos_pasan()
    {
        // Arrange
        $request = new UpdateMesaRequest();
        $request->setRouteResolver(function () {
            return new class($this->mesa->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosValidos = [
            'id_mesa' => 'MESA-001-ACTUALIZADA',
            'provincia_id' => $this->provincia->id,
            'circuito' => '002',
            'establecimiento' => 'Escuela Actualizada',
            'electores' => 1200
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_solo_circuito_opcional()
    {
        // Arrange
        $request = new UpdateMesaRequest();
        $request->setRouteResolver(function () {
            return new class($this->mesa->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosParciales = [
            'id_mesa' => 'MESA-001',
            'provincia_id' => $this->provincia->id,
            'circuito' => '002',
            'electores' => 1200
            // Proporcionamos todos los campos requeridos
        ];

        // Act
        $validator = Validator::make($datosParciales, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_solo_establecimiento_opcional()
    {
        // Arrange
        $request = new UpdateMesaRequest();
        $request->setRouteResolver(function () {
            return new class($this->mesa->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosParciales = [
            'id_mesa' => 'MESA-001',
            'provincia_id' => $this->provincia->id,
            'establecimiento' => 'Escuela Actualizada',
            'electores' => 1200
            // Proporcionamos todos los campos requeridos
        ];

        // Act
        $validator = Validator::make($datosParciales, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_cambiar_electores_mantiene_minimo()
    {
        // Arrange
        $request = new UpdateMesaRequest();
        $request->setRouteResolver(function () {
            return new class($this->mesa->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosInvalidos = [
            'id_mesa' => 'MESA-001',
            'provincia_id' => $this->provincia->id,
            'electores' => 0 // Menor a 1
        ];

        // Act
        $validator = Validator::make($datosInvalidos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('electores'));
    }

    /** @test */
    public function test_update_request_campos_requeridos_faltantes_fallan()
    {
        // Arrange
        $request = new UpdateMesaRequest();
        $datosIncompletos = [
            // Faltan campos requeridos
        ];

        // Act
        $validator = Validator::make($datosIncompletos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('id_mesa'));
        $this->assertTrue($validator->errors()->has('provincia_id'));
        $this->assertTrue($validator->errors()->has('electores'));
    }

    /** @test */
    public function test_mensajes_error_en_espanol()
    {
        // Arrange
        $request = new StoreMesaRequest();
        $datos = [];

        // Act
        $validator = Validator::make($datos, $request->rules(), $request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals('El identificador de mesa es obligatorio', $validator->errors()->first('id_mesa'));
        $this->assertEquals('La provincia es obligatoria', $validator->errors()->first('provincia_id'));
        $this->assertEquals('La cantidad de electores es obligatoria', $validator->errors()->first('electores'));
    }

    /** @test */
    public function test_update_request_mensajes_error_en_espanol()
    {
        // Arrange
        $request = new UpdateMesaRequest();
        $request->setRouteResolver(function () {
            return new class($this->mesa->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });
        $datos = [];

        // Act
        $validator = Validator::make($datos, $request->rules(), $request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals('El identificador de mesa es obligatorio', $validator->errors()->first('id_mesa'));
        $this->assertEquals('La provincia es obligatoria', $validator->errors()->first('provincia_id'));
        $this->assertEquals('La cantidad de electores es obligatoria', $validator->errors()->first('electores'));
    }
}
