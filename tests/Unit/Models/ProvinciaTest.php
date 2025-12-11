<?php

namespace Tests\Unit\Models;

use App\Models\Provincia;
use App\Models\Lista;
use App\Models\Candidato;
use App\Models\Mesa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvinciaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_table_name_is_provincias()
    {
        // Act & Assert
        $this->assertEquals('provincias', (new Provincia)->getTable());
    }

    /** @test */
    public function test_fillable_attributes()
    {
        // Arrange
        $fillable = ['nombre', 'codigo'];

        // Act & Assert
        $this->assertEquals($fillable, (new Provincia)->getFillable());
    }

    /** @test */
    public function test_casts_configuration()
    {
        // Arrange
        $model = new Provincia();

        // Act & Assert
        $this->assertIsArray($model->getCasts());
    }

    /** @test */
    public function test_has_many_listas_relationship()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $listas = Lista::factory()->count(3)->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $provincia->listas);
        $this->assertCount(3, $provincia->listas);
        $this->assertTrue($provincia->listas->contains($listas->first()));
    }

    /** @test */
    public function test_has_many_candidatos_relationship()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $candidatos = Candidato::factory()->count(5)->create([
            'provincia_id' => $provincia->id
        ]);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $provincia->candidatos);
        $this->assertCount(5, $provincia->candidatos);
        $this->assertTrue($provincia->candidatos->contains($candidatos->first()));
    }

    /** @test */
    public function test_has_many_mesas_relationship()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesas = Mesa::factory()->count(8)->create([
            'provincia_id' => $provincia->id
        ]);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $provincia->mesas);
        $this->assertCount(8, $provincia->mesas);
        $this->assertTrue($provincia->mesas->contains($mesas->first()));
    }

    /** @test */
    public function test_provincia_has_correct_timestamps()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act & Assert
        $this->assertNotNull($provincia->created_at);
        $this->assertNotNull($provincia->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $provincia->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $provincia->updated_at);
    }

    /** @test */
    public function test_candidatos_relationship_returns_empty_when_no_candidatos()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $candidatos = $provincia->candidatos;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $candidatos);
        $this->assertCount(0, $candidatos);
    }

    /** @test */
    public function test_listas_relationship_returns_empty_when_no_listas()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $listas = $provincia->listas;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $listas);
        $this->assertCount(0, $listas);
    }

    /** @test */
    public function test_mesas_relationship_returns_empty_when_no_mesas()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesas = $provincia->mesas;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $mesas);
        $this->assertCount(0, $mesas);
    }

    /** @test */
    public function test_can_create_provincia_with_minimal_data()
    {
        // Act
        $provincia = Provincia::create([
            'nombre' => 'Test Provincia',
            'codigo' => 'TP'
        ]);

        // Assert
        $this->assertDatabaseHas('provincias', [
            'nombre' => 'Test Provincia',
            'codigo' => 'TP'
        ]);
        $this->assertEquals('Test Provincia', $provincia->nombre);
        $this->assertEquals('TP', $provincia->codigo);
    }

    /** @test */
    public function test_provincia_name_and_codigo_are_strings()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act & Assert
        $this->assertIsString($provincia->nombre);
        $this->assertIsString($provincia->codigo);
    }
}
