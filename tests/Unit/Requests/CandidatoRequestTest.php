<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreCandidatoRequest;
use App\Http\Requests\UpdateCandidatoRequest;
use App\Models\Candidato;
use App\Models\Lista;
use App\Models\Provincia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CandidatoRequestTest extends TestCase
{
    use RefreshDatabase;

    private Provincia $provincia;
    private Lista $listaDip;
    private Lista $listaSen;
    private Candidato $candidato;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos comunes para los tests
        $this->provincia = Provincia::create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $this->listaDip = Lista::create([
            'nombre' => 'Lista Diputados',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $this->listaSen = Lista::create([
            'nombre' => 'Lista Senadores',
            'provincia_id' => $this->provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        $this->candidato = Candidato::create([
            'nombre' => 'Candidato Test',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 1
        ]);
    }

    // ===== Tests para StoreCandidatoRequest =====

    /** @test */
    public function test_store_request_con_datos_validos_pasa_validacion()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datosValidos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_con_observaciones_opcional_pasa()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datosValidos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2,
            'observaciones' => 'Candidato suplente'
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_sin_observaciones_pasa()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datosValidos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2,
            'observaciones' => null
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
        $request = new StoreCandidatoRequest();
        $datos = [
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2
            // Falta nombre
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_store_request_lista_id_requerido_falla()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2
            // Falta lista_id
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('lista_id'));
    }

    /** @test */
    public function test_store_request_provincia_id_requerido_falla()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2
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
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'orden' => 2
            // Falta cargo
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('cargo'));
    }

    /** @test */
    public function test_store_request_orden_requerido_falla()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS
            // Falta orden
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('orden'));
    }

    /** @test */
    public function test_store_request_cargo_invalido_falla()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => 'PRESIDENTE', // Cargo inválido
            'orden' => 2
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('cargo'));
    }

    /** @test */
    public function test_store_request_lista_inexistente_falla()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => 99999, // ID que no existe
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('lista_id'));
    }

    /** @test */
    public function test_store_request_provincia_inexistente_falla()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => 99999, // ID que no existe
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('provincia_id'));
    }

    /** @test */
    public function test_store_request_orden_duplicado_misma_lista_falla()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 1 // Ya existe orden 1 en esta lista
        ];
        $request->merge($datos);

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('orden'));
    }

    /** @test */
    public function test_store_request_orden_duplicado_diferente_lista_pasa()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaSen->id, // Diferente lista
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_SENADORES,
            'orden' => 1 // Puede tener orden 1 en otra lista
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_orden_minimo_1()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 0 // Menor a 1
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('orden'));
    }

    /** @test */
    public function test_store_request_nombre_max_150_caracteres()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $nombreLargo = str_repeat('a', 151);
        $datos = [
            'nombre' => $nombreLargo,
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('nombre'));
    }

    /** @test */
    public function test_store_request_cargo_no_coincide_con_lista_falla()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $request->setContainer($this->app);
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id, // Lista de DIPUTADOS
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_SENADORES, // Pero cargo SENADORES
            'orden' => 2
        ];
        $request->merge($datos);

        // Act
        $validator = Validator::make($datos, $request->rules());
        $request->withValidator($validator);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('cargo'));
    }

    /** @test */
    public function test_store_request_cargo_coincide_con_lista_diputados_pasa()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaDip->id, // Lista de DIPUTADOS
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS, // Cargo DIPUTADOS
            'orden' => 2
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_cargo_coincide_con_lista_senadores_pasa()
    {
        // Arrange
        $request = new StoreCandidatoRequest();
        $datos = [
            'nombre' => 'Nuevo Candidato',
            'lista_id' => $this->listaSen->id, // Lista de SENADORES
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_SENADORES, // Cargo SENADORES
            'orden' => 2
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    // ===== Tests para UpdateCandidatoRequest =====

    /** @test */
    public function test_update_request_mismo_registro_no_falla_unique_orden()
    {
        // Arrange
        $request = new UpdateCandidatoRequest();
        $request->setRouteResolver(function () {
            return new class($this->candidato->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosActualizados = [
            'nombre' => 'Candidato Actualizado',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 1 // Mismo orden (debería pasar)
        ];

        // Act
        $validator = Validator::make($datosActualizados, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_cambio_a_orden_duplicado_misma_lista_falla()
    {
        // Arrange: Crear otro candidato en la misma lista
        $otroCandidato = Candidato::create([
            'nombre' => 'Otro Candidato',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2
        ]);

        $request = new UpdateCandidatoRequest();
        $request->setRouteResolver(function () {
            return new class($this->candidato->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosConflicto = [
            'nombre' => 'Candidato Actualizado',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 2 // Ya existe en esta lista
        ];
        $request->merge($datosConflicto);

        // Act
        $validator = Validator::make($datosConflicto, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('orden'));
    }

    /** @test */
    public function test_update_request_cambio_lista_pasa_validacion()
    {
        // Arrange
        $request = new UpdateCandidatoRequest();
        $request->setRouteResolver(function () {
            return new class($this->candidato->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosValidos = [
            'nombre' => 'Candidato Actualizado',
            'lista_id' => $this->listaSen->id, // Cambiar a lista de SENADORES
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_SENADORES,
            'orden' => 1
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_cambio_cargo_no_coincide_con_lista_falla()
    {
        // Arrange
        $request = new UpdateCandidatoRequest();
        $request->setContainer($this->app);
        $request->setRouteResolver(function () {
            return new class($this->candidato->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datos = [
            'nombre' => 'Candidato Actualizado',
            'lista_id' => $this->listaDip->id, // Lista de DIPUTADOS
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_SENADORES, // Pero cargo SENADORES
            'orden' => 1
        ];
        $request->merge($datos);

        // Act
        $validator = Validator::make($datos, $request->rules());
        $request->withValidator($validator);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('cargo'));
    }

    /** @test */
    public function test_update_request_datos_validos_pasan()
    {
        // Arrange
        $request = new UpdateCandidatoRequest();
        $request->setRouteResolver(function () {
            return new class($this->candidato->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        $datosValidos = [
            'nombre' => 'Candidato Actualizado',
            'lista_id' => $this->listaDip->id,
            'provincia_id' => $this->provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 3,
            'observaciones' => 'Observaciones actualizadas'
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }
}
