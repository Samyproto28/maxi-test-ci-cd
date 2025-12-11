<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreTelegramaRequest;
use App\Http\Requests\UpdateTelegramaRequest;
use App\Models\Mesa;
use App\Models\Lista;
use App\Models\Provincia;
use App\Models\Telegrama;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TelegramaRequestTest extends TestCase
{
    use RefreshDatabase;

    private Provincia $provincia;
    private Mesa $mesa;
    private Lista $lista;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear datos comunes para los tests
        $this->provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $this->mesa = Mesa::create([
            'id_mesa' => 'MESA-TEST-001',
            'provincia_id' => $this->provincia->id,
            'electores' => 1000
        ]);
        $this->lista = Lista::create([
            'nombre' => 'Lista Test',
            'provincia_id' => $this->provincia->id,
            'cargo' => 'DIPUTADOS'
        ]);
    }

    // ===== Tests para StoreTelegramaRequest =====

    /** @test */
    public function test_store_request_con_datos_validos_pasa_validacion()
    {
        // Arrange
        $request = new StoreTelegramaRequest();
        $datosValidos = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ];

        // Act
        $validator = Validator::make($datosValidos, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_store_request_votos_negativos_fallan()
    {
        // Arrange
        $request = new StoreTelegramaRequest();
        $datosNegativos = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => -10,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ];

        // Act
        $validator = Validator::make($datosNegativos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('votos_diputados'));
    }

    /** @test */
    public function test_store_request_suma_mayor_a_electores_falla()
    {
        // Arrange
        $request = new StoreTelegramaRequest();
        $request->setContainer($this->app);

        $datos = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 500,
            'votos_senadores' => 500,
            'blancos' => 100,
            'nulos' => 50,
            'recurridos' => 50, // Total: 1200 > 1000 electores
            'usuario' => 'test_user'
        ];

        $request->merge($datos);

        // Act
        $validator = Validator::make($datos, $request->rules());
        $request->withValidator($validator);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('votos'));
    }

    /** @test */
    public function test_store_request_mesa_inexistente_falla()
    {
        // Arrange
        $request = new StoreTelegramaRequest();
        $datos = [
            'mesa_id' => 99999, // ID que no existe
            'lista_id' => $this->lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('mesa_id'));
    }

    /** @test */
    public function test_store_request_lista_inexistente_falla()
    {
        // Arrange
        $request = new StoreTelegramaRequest();
        $datos = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => 99999, // ID que no existe
            'votos_diputados' => 100,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ];

        // Act
        $validator = Validator::make($datos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('lista_id'));
    }

    /** @test */
    public function test_store_request_duplicado_mesa_lista_falla()
    {
        // Arrange: Crear telegrama existente
        Telegrama::create([
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ]);

        $request = new StoreTelegramaRequest();
        $request->merge(['mesa_id' => $this->mesa->id]);

        $datosDuplicados = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id, // Misma mesa + lista
            'votos_diputados' => 50,
            'votos_senadores' => 50,
            'blancos' => 20,
            'nulos' => 10,
            'recurridos' => 10,
            'usuario' => 'otro_usuario'
        ];

        // Act
        $validator = Validator::make($datosDuplicados, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('lista_id'));
    }

    /** @test */
    public function test_store_request_usuario_vacio_falla()
    {
        // Arrange
        $request = new StoreTelegramaRequest();
        $datosSinUsuario = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => '' // Usuario vacío
        ];

        // Act
        $validator = Validator::make($datosSinUsuario, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('usuario'));
    }

    /** @test */
    public function test_store_request_campos_requeridos_faltantes()
    {
        // Arrange
        $request = new StoreTelegramaRequest();
        $datosIncompletos = [
            'mesa_id' => $this->mesa->id,
            // Falta lista_id y otros campos
        ];

        // Act
        $validator = Validator::make($datosIncompletos, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('lista_id'));
        $this->assertTrue($validator->errors()->has('votos_diputados'));
        $this->assertTrue($validator->errors()->has('votos_senadores'));
        $this->assertTrue($validator->errors()->has('blancos'));
        $this->assertTrue($validator->errors()->has('nulos'));
        $this->assertTrue($validator->errors()->has('recurridos'));
        $this->assertTrue($validator->errors()->has('usuario'));
    }

    // ===== Tests para UpdateTelegramaRequest =====

    /** @test */
    public function test_update_request_mismo_registro_no_falla_unique()
    {
        // Arrange: Crear telegrama existente
        $telegrama = Telegrama::create([
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ]);

        $request = new UpdateTelegramaRequest();
        $request->setRouteResolver(function () use ($telegrama) {
            return new class($telegrama->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });
        $request->merge(['mesa_id' => $this->mesa->id]);

        $datosActualizados = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id, // Misma mesa + lista (debería pasar)
            'votos_diputados' => 200,
            'votos_senadores' => 200,
            'blancos' => 60,
            'nulos' => 40,
            'recurridos' => 30,
            'usuario' => 'test_user_updated'
        ];

        // Act
        $validator = Validator::make($datosActualizados, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_excluye_votos_actuales_en_suma()
    {
        // Arrange: Crear telegrama con 300 votos
        $telegrama = Telegrama::create([
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 100,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20, // Total: 300
            'usuario' => 'test_user'
        ]);

        $request = new UpdateTelegramaRequest();
        $request->setContainer($this->app);
        $request->setRouteResolver(function () use ($telegrama) {
            return new class($telegrama->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        // Actualizar a 900 votos (debería pasar porque excluye los 300 anteriores)
        $datosActualizados = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 300,
            'votos_senadores' => 300,
            'blancos' => 150,
            'nulos' => 90,
            'recurridos' => 60, // Total: 900 (sin contar los 300 anteriores)
            'usuario' => 'test_user'
        ];

        $request->merge($datosActualizados);

        // Act
        $validator = Validator::make($datosActualizados, $request->rules());
        $request->withValidator($validator);

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_update_request_cambio_a_lista_duplicada_falla()
    {
        // Arrange: Crear dos telegramas en la misma mesa
        $lista2 = Lista::create([
            'nombre' => 'Lista 2',
            'provincia_id' => $this->provincia->id,
            'cargo' => 'SENADORES'
        ]);

        Telegrama::create([
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 150,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20,
            'usuario' => 'test_user'
        ]);

        $telegrama2 = Telegrama::create([
            'mesa_id' => $this->mesa->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 80,
            'votos_senadores' => 120,
            'blancos' => 40,
            'nulos' => 20,
            'recurridos' => 10,
            'usuario' => 'test_user'
        ]);

        $request = new UpdateTelegramaRequest();
        $request->setRouteResolver(function () use ($telegrama2) {
            return new class($telegrama2->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });
        $request->merge(['mesa_id' => $this->mesa->id]);

        // Intentar cambiar telegrama2 a lista 1 (que ya existe en esta mesa)
        $datosConflicto = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id, // Ya existe con el otro telegrama
            'votos_diputados' => 90,
            'votos_senadores' => 130,
            'blancos' => 45,
            'nulos' => 25,
            'recurridos' => 15,
            'usuario' => 'test_user'
        ];

        // Act
        $validator = Validator::make($datosConflicto, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('lista_id'));
    }

    /** @test */
    public function test_update_request_validacion_suma_correcta()
    {
        // Arrange: Crear telegrama existente con 500 votos
        $telegrama = Telegrama::create([
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 200,
            'votos_senadores' => 150,
            'blancos' => 100,
            'nulos' => 30,
            'recurridos' => 20, // Total: 500
            'usuario' => 'test_user'
        ]);

        $request = new UpdateTelegramaRequest();
        $request->setContainer($this->app);
        $request->setRouteResolver(function () use ($telegrama) {
            return new class($telegrama->id) {
                public function __construct(private $id) {}
                public function parameter($key) { return $this->id; }
            };
        });

        // Intentar actualizar a 1200 votos (debería fallar porque excede 1000 electores)
        $datosExceso = [
            'mesa_id' => $this->mesa->id,
            'lista_id' => $this->lista->id,
            'votos_diputados' => 400,
            'votos_senadores' => 400,
            'blancos' => 200,
            'nulos' => 120,
            'recurridos' => 80, // Total: 1200 > 1000
            'usuario' => 'test_user'
        ];

        $request->merge($datosExceso);

        // Act
        $validator = Validator::make($datosExceso, $request->rules());
        $request->withValidator($validator);

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('votos'));
    }
}
