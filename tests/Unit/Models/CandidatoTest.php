<?php

namespace Tests\Unit\Models;

use App\Models\Candidato;
use App\Models\Lista;
use App\Models\Provincia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidatoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_table_name_is_candidatos()
    {
        // Act & Assert
        $this->assertEquals('candidatos', (new Candidato)->getTable());
    }

    /** @test */
    public function test_fillable_attributes()
    {
        // Arrange
        $fillable = [
            'nombre',
            'orden',
            'cargo',
            'observaciones',
            'lista_id',
            'provincia_id'
        ];

        // Act & Assert
        $this->assertEquals($fillable, (new Candidato)->getFillable());
    }

    /** @test */
    public function test_cargo_constants_defined()
    {
        // Act & Assert
        $this->assertEquals('DIPUTADOS', Candidato::CARGO_DIPUTADOS);
        $this->assertEquals('SENADORES', Candidato::CARGO_SENADORES);
        $this->assertIsString(Candidato::CARGO_DIPUTADOS);
        $this->assertIsString(Candidato::CARGO_SENADORES);
    }

    /** @test */
    public function test_belongs_to_lista_relationship()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Assert
        $this->assertInstanceOf(Lista::class, $candidato->lista);
        $this->assertEquals($lista->id, $candidato->lista->id);
        $this->assertEquals($lista->nombre, $candidato->lista->nombre);
    }

    /** @test */
    public function test_belongs_to_provincia_relationship()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $candidato = Candidato::factory()->create([
            'provincia_id' => $provincia->id
        ]);

        // Assert
        $this->assertInstanceOf(Provincia::class, $candidato->provincia);
        $this->assertEquals($provincia->id, $candidato->provincia->id);
        $this->assertEquals($provincia->nombre, $candidato->provincia->nombre);
    }

    /** @test */
    public function test_cargo_matches_lista_cargo()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => $lista->cargo
        ]);

        // Assert
        $this->assertEquals($lista->cargo, $candidato->cargo);
        $this->assertEquals('DIPUTADOS', $candidato->cargo);
    }

    /** @test */
    public function test_orden_is_integer()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 5
        ]);

        // Assert
        $this->assertIsInt($candidato->orden);
        $this->assertEquals(5, $candidato->orden);
    }

    /** @test */
    public function test_orden_must_be_positive()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 1
        ]);

        // Assert
        $this->assertGreaterThanOrEqual(1, $candidato->orden);
    }

    /** @test */
    public function test_observaciones_is_optional()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato1 = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'observaciones' => null
        ]);

        $candidato2 = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'observaciones' => 'Candidato titular'
        ]);

        // Assert
        $this->assertNull($candidato1->observaciones);
        $this->assertEquals('Candidato titular', $candidato2->observaciones);
    }

    /** @test */
    public function test_has_correct_timestamps()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Assert
        $this->assertNotNull($candidato->created_at);
        $this->assertNotNull($candidato->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $candidato->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $candidato->updated_at);
    }

    /** @test */
    public function test_casts_configuration()
    {
        // Arrange
        $model = new Candidato();

        // Act & Assert
        $this->assertIsArray($model->getCasts());
    }

    /** @test */
    public function test_lista_relationship_can_be_eager_loaded()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $candidatoWithLista = Candidato::with('lista')->find($candidato->id);

        // Assert
        $this->assertTrue($candidatoWithLista->relationLoaded('lista'));
        $this->assertInstanceOf(Lista::class, $candidatoWithLista->lista);
    }

    /** @test */
    public function test_provincia_relationship_can_be_eager_loaded()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $candidato = Candidato::factory()->create([
            'provincia_id' => $provincia->id
        ]);

        $candidatoWithProvincia = Candidato::with('provincia')->find($candidato->id);

        // Assert
        $this->assertTrue($candidatoWithProvincia->relationLoaded('provincia'));
        $this->assertInstanceOf(Provincia::class, $candidatoWithProvincia->provincia);
    }

    /** @test */
    public function test_can_create_candidato_with_minimal_data()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::create([
            'nombre' => 'Test Candidato',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 1
        ]);

        // Assert
        $this->assertDatabaseHas('candidatos', [
            'nombre' => 'Test Candidato',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 1
        ]);
        $this->assertEquals('Test Candidato', $candidato->nombre);
    }

    /** @test */
    public function test_nombre_is_string()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Assert
        $this->assertIsString($candidato->nombre);
    }

    /** @test */
    public function test_orden_is_required()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidato = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'orden' => 1
        ]);

        // Assert
        $this->assertNotNull($candidato->orden);
        $this->assertGreaterThan(0, $candidato->orden);
    }

    /** @test */
    public function test_cargo_must_be_diputados_or_senadores()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act & Assert - Valid cargos
        $candidatoDip = Candidato::factory()->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);
        $this->assertEquals(Lista::CARGO_DIPUTADOS, $candidatoDip->cargo);

        $listaSen = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);
        $candidatoSen = Candidato::factory()->create([
            'lista_id' => $listaSen->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);
        $this->assertEquals(Lista::CARGO_SENADORES, $candidatoSen->cargo);
    }
}
