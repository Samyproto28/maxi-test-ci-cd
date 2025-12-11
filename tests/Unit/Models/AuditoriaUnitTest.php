<?php

namespace Tests\Unit\Models;

use App\Models\Auditoria;
use PHPUnit\Framework\TestCase;

/**
 * Pure unit tests for Auditoria model (no database required)
 */
class AuditoriaUnitTest extends TestCase
{
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
     * Test that casts configuration is correct
     */
    public function test_casts_configuration(): void
    {
        $auditoria = new Auditoria();
        $casts = $auditoria->getCasts();

        $this->assertArrayHasKey('datos_anteriores', $casts);
        $this->assertArrayHasKey('datos_nuevos', $casts);
        $this->assertArrayHasKey('created_at', $casts);

        $this->assertEquals('array', $casts['datos_anteriores']);
        $this->assertEquals('array', $casts['datos_nuevos']);
        $this->assertEquals('datetime', $casts['created_at']);
    }

    /**
     * Test that registrar method exists and is callable
     */
    public function test_registrar_method_exists(): void
    {
        $this->assertTrue(method_exists(Auditoria::class, 'registrar'));
        $this->assertTrue(is_callable([Auditoria::class, 'registrar']));
    }

    /**
     * Test registrar method signature
     */
    public function test_registrar_method_signature(): void
    {
        $reflection = new \ReflectionMethod(Auditoria::class, 'registrar');

        // Check it's static
        $this->assertTrue($reflection->isStatic());

        // Check it's public
        $this->assertTrue($reflection->isPublic());

        // Check parameter count
        $this->assertEquals(6, $reflection->getNumberOfParameters());

        // Check return type
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('self', $returnType->getName());
    }
}
