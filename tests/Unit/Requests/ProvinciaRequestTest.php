<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreProvinciaRequest;
use App\Http\Requests\UpdateProvinciaRequest;
use App\Models\Provincia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProvinciaRequestTest extends TestCase
{
    use RefreshDatabase;

    private Provincia $provincia;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear provincia de prueba
        $this->provincia = Provincia::create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);
    }

    // ===== Tests para StoreProvinciaRequest =====

    /** @test */
    public function test_store_request_con_datos_validos_pasa_validacion()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $datosValidos = [
            'nombre' => 'Córdoba',
            'codigo' => 'CB'
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
        $request = new StoreProvinciaRequest();
        $datos = [
            'codigo' => 'CB'
            // Falta nombre
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_store_request_codigo_requerido_falla()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $datos = [
            'nombre' => 'Córdoba'
            // Falta codigo
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('codigo'));
    }

    /** @test */
    public function test_store_request_nombre_duplicado_falla()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $datos = [
            'nombre' => 'Buenos Aires', // Ya existe
            'codigo' => 'CB'
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_store_request_codigo_duplicado_falla()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $datos = [
            'nombre' => 'Córdoba',
            'codigo' => 'BA' // Ya existe
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('codigo'));
    }

    /** @test */
    public function test_store_request_nombre_max_100_caracteres()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $nombreLargo = str_repeat('a', 101);
        $datos = [
            'nombre' => $nombreLargo,
            'codigo' => 'CB'
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_store_request_codigo_max_10_caracteres()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $codigoLargo = str_repeat('A', 11);
        $datos = [
            'nombre' => 'Córdoba',
            'codigo' => $codigoLargo
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('codigo'));
    }

    /** @test */
    public function test_store_request_codigo_solo_mayusculas_y_numeros()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $datos = [
            'nombre' => 'Córdoba',
            'codigo' => 'CB-123' // Caracter especial no permitido
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('codigo'));
    }

    /** @test */
    public function test_store_request_codigo_con_numeros_pasa()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $datos = [
            'nombre' => 'Ciudad Autónoma',
            'codigo' => 'CABA1'
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    // ===== Tests para UpdateProvinciaRequest =====

    /** @test */
    public function test_update_request_mismo_registro_no_falla_unique_nombre()
    {
        // Arrange
        $request = new UpdateProvinciaRequest();
        $request->setRouteResolver(function () {
            return new class($this->provincia->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosActualizados = [
            'nombre' => 'Buenos Aires', // Mismo nombre (debería pasar)
            'codigo' => 'BA2'
        ];

        // Act
        $validator = Validator::make($datosActualizados, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_mismo_registro_no_falla_unique_codigo()
    {
        // Arrange
        $request = new UpdateProvinciaRequest();
        $request->setRouteResolver(function () {
            return new class($this->provincia->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosActualizados = [
            'nombre' => 'Buenos Aires Actualizado',
            'codigo' => 'BA' // Mismo código (debería pasar)
        ];

        // Act
        $validator = Validator::make($datosActualizados, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_cambio_a_nombre_duplicado_falla()
    {
        // Arrange: Crear otra provincia
        $otraProvincia = Provincia::create([
            'nombre' => 'Córdoba',
            'codigo' => 'CB'
        ]);

        $request = new UpdateProvinciaRequest();
        $request->setRouteResolver(function () {
            return new class($this->provincia->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosConflicto = [
            'nombre' => 'Córdoba', // Ya existe en otra provincia
            'codigo' => 'BA2'
        ];

        // Act
        $validator = Validator::make($datosConflicto, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_update_request_cambio_a_codigo_duplicado_falla()
    {
        // Arrange: Crear otra provincia
        $otraProvincia = Provincia::create([
            'nombre' => 'Córdoba',
            'codigo' => 'CB'
        ]);

        $request = new UpdateProvinciaRequest();
        $request->setRouteResolver(function () {
            return new class($this->provincia->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosConflicto = [
            'nombre' => 'Buenos Aires Actualizado',
            'codigo' => 'CB' // Ya existe en otra provincia
        ];

        // Act
        $validator = Validator::make($datosConflicto, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('codigo'));
    }

    /** @test */
    public function test_update_request_datos_validos_pasan()
    {
        // Arrange
        $request = new UpdateProvinciaRequest();
        $request->setRouteResolver(function () {
            return new class($this->provincia->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosValidos = [
            'nombre' => 'Buenos Aires Actualizado',
            'codigo' => 'BA2'
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_campos_requeridos_faltantes_fallan()
    {
        // Arrange
        $request = new UpdateProvinciaRequest();
        $datosIncompletos = [
            // Faltan nombre y codigo
        ];

        // Act
        $validator = Validator::make($datosIncompletos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
        $this->assertTrue($validator->errors()->has('codigo'));
    }

    /** @test */
    public function test_mensajes_error_en_espanol()
    {
        // Arrange
        $request = new StoreProvinciaRequest();
        $datos = [];

        // Act
        $validator = Validator::make($datos, $request->rules(), $request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals('El nombre de la provincia es obligatorio', $validator->errors()->first('nombre'));
        $this->assertEquals('El código de provincia es obligatorio', $validator->errors()->first('codigo'));
    }

    /** @test */
    public function test_update_request_mensajes_error_en_espanol()
    {
        // Arrange
        $request = new UpdateProvinciaRequest();
        $request->setRouteResolver(function () {
            return new class($this->provincia->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });
        $datos = [];

        // Act
        $validator = Validator::make($datos, $request->rules(), $request->messages());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertEquals('El nombre de la provincia es obligatorio', $validator->errors()->first('nombre'));
        $this->assertEquals('El código de provincia es obligatorio', $validator->errors()->first('codigo'));
    }
}
