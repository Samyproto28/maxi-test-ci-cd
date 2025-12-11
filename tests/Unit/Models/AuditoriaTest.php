<?php

namespace Tests\Unit\Models;

use App\Models\Auditoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that constants have correct values
     */
    public function test_constants_have_correct_values(): void
    {
        $this->assertEquals('CREATE', Auditoria::ACCION_CREATE);
        $this->assertEquals('UPDATE', Auditoria::ACCION_UPDATE);
        $this->assertEquals('DELETE', Auditoria::ACCION_DELETE);
    }

    /**
     * Test that ACCIONES array contains all action constants
     */
    public function test_acciones_array_contains_all_constants(): void
    {
        $this->assertIsArray(Auditoria::ACCIONES);
        $this->assertCount(3, Auditoria::ACCIONES);
        $this->assertContains('CREATE', Auditoria::ACCIONES);
        $this->assertContains('UPDATE', Auditoria::ACCIONES);
        $this->assertContains('DELETE', Auditoria::ACCIONES);
    }

    /**
     * Test that table name is configured correctly
     */
    public function test_table_name_is_auditoria(): void
    {
        $auditoria = new Auditoria();
        $this->assertEquals('auditoria', $auditoria->getTable());
    }

    /**
     * Test that timestamps is false (only uses created_at)
     */
    public function test_timestamps_is_false(): void
    {
        $auditoria = new Auditoria();
        $this->assertFalse($auditoria->timestamps);
    }

    /**
     * Test that fillable contains all required fields
     */
    public function test_fillable_contains_required_fields(): void
    {
        $auditoria = new Auditoria();
        $fillable = $auditoria->getFillable();

        $this->assertCount(6, $fillable);
        $this->assertContains('tabla', $fillable);
        $this->assertContains('registro_id', $fillable);
        $this->assertContains('accion', $fillable);
        $this->assertContains('datos_anteriores', $fillable);
        $this->assertContains('datos_nuevos', $fillable);
        $this->assertContains('usuario', $fillable);
    }

    /**
     * Test that JSON casts work correctly (array to JSON and back)
     */
    public function test_json_casts_work_correctly(): void
    {
        $datosAnteriores = ['campo1' => 'valor1', 'campo2' => 123];
        $datosNuevos = ['campo1' => 'valor2', 'campo2' => 456];

        $auditoria = Auditoria::registrar(
            'usuarios',
            1,
            Auditoria::ACCION_UPDATE,
            $datosAnteriores,
            $datosNuevos,
            'test_user'
        );

        // Refresh from DB to ensure we're getting data from database
        $auditoria->refresh();

        $this->assertIsArray($auditoria->datos_anteriores);
        $this->assertIsArray($auditoria->datos_nuevos);
        $this->assertEquals($datosAnteriores, $auditoria->datos_anteriores);
        $this->assertEquals($datosNuevos, $auditoria->datos_nuevos);
    }

    /**
     * Test that complex nested data serializes/deserializes correctly
     */
    public function test_complex_nested_data_serializes_correctly(): void
    {
        $complexData = [
            'nivel1' => [
                'nivel2' => [
                    'nivel3' => 'valor profundo',
                    'array' => [1, 2, 3, 4, 5]
                ],
                'otro_campo' => true
            ],
            'campo_simple' => 'valor'
        ];

        $auditoria = Auditoria::registrar(
            'configuracion',
            1,
            Auditoria::ACCION_UPDATE,
            null,
            $complexData,
            'admin'
        );

        $auditoria->refresh();

        $this->assertEquals($complexData, $auditoria->datos_nuevos);
        $this->assertEquals('valor profundo', $auditoria->datos_nuevos['nivel1']['nivel2']['nivel3']);
        $this->assertEquals([1, 2, 3, 4, 5], $auditoria->datos_nuevos['nivel1']['nivel2']['array']);
    }

    /**
     * Test that null values in JSON fields are handled correctly
     */
    public function test_null_values_are_handled_correctly(): void
    {
        // CREATE action: datos_anteriores should be null
        $auditoriaCreate = Auditoria::registrar(
            'usuarios',
            1,
            Auditoria::ACCION_CREATE,
            null,
            ['nombre' => 'Juan'],
            'admin'
        );

        $this->assertNull($auditoriaCreate->datos_anteriores);
        $this->assertIsArray($auditoriaCreate->datos_nuevos);

        // DELETE action: datos_nuevos should be null
        $auditoriaDelete = Auditoria::registrar(
            'usuarios',
            1,
            Auditoria::ACCION_DELETE,
            ['nombre' => 'Juan'],
            null,
            'admin'
        );

        $this->assertIsArray($auditoriaDelete->datos_anteriores);
        $this->assertNull($auditoriaDelete->datos_nuevos);
    }

    /**
     * Test that registrar() method creates audit record correctly
     */
    public function test_registrar_creates_audit_record(): void
    {
        $auditoria = Auditoria::registrar(
            'productos',
            123,
            Auditoria::ACCION_CREATE,
            null,
            ['nombre' => 'Producto Test', 'precio' => 100],
            'usuario_test'
        );

        $this->assertInstanceOf(Auditoria::class, $auditoria);
        $this->assertTrue($auditoria->exists);
        $this->assertEquals('productos', $auditoria->tabla);
        $this->assertEquals(123, $auditoria->registro_id);
        $this->assertEquals('CREATE', $auditoria->accion);
        $this->assertEquals('usuario_test', $auditoria->usuario);
        $this->assertNull($auditoria->datos_anteriores);
        $this->assertIsArray($auditoria->datos_nuevos);
    }

    /**
     * Test that created_at is set automatically and is a datetime
     */
    public function test_created_at_is_set_automatically(): void
    {
        $auditoria = Auditoria::registrar(
            'test',
            1,
            Auditoria::ACCION_CREATE,
            null,
            ['data' => 'test'],
            'user'
        );

        $this->assertNotNull($auditoria->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $auditoria->created_at);
    }

    /**
     * Test that updated_at is not created
     */
    public function test_updated_at_is_not_created(): void
    {
        $auditoria = Auditoria::registrar(
            'test',
            1,
            Auditoria::ACCION_CREATE,
            null,
            ['data' => 'test'],
            'user'
        );

        $this->assertObjectNotHasProperty('updated_at', $auditoria);
    }

    /**
     * Test mass assignment works with create()
     */
    public function test_mass_assignment_works(): void
    {
        $data = [
            'tabla' => 'categorias',
            'registro_id' => 99,
            'accion' => 'UPDATE',
            'datos_anteriores' => ['nombre' => 'Viejo'],
            'datos_nuevos' => ['nombre' => 'Nuevo'],
            'usuario' => 'admin'
        ];

        $auditoria = Auditoria::create($data);

        $this->assertTrue($auditoria->exists);
        $this->assertEquals('categorias', $auditoria->tabla);
        $this->assertEquals(99, $auditoria->registro_id);
    }
}
