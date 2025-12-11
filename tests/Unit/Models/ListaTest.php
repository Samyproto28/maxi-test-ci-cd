<?php

namespace Tests\Unit\Models;

use App\Models\Lista;
use App\Models\Provincia;
use App\Models\Candidato;
use App\Models\Telegrama;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_table_name_is_listas()
    {
        // Act & Assert
        $this->assertEquals('listas', (new Lista)->getTable());
    }

    /** @test */
    public function test_fillable_attributes()
    {
        // Arrange
        $fillable = [
            'nombre',
            'alianza',
            'color',
            'provincia_id',
            'cargo'
        ];

        // Act & Assert
        $this->assertEquals($fillable, (new Lista)->getFillable());
    }

    /** @test */
    public function test_cargo_constants_defined()
    {
        // Act & Assert
        $this->assertEquals('DIPUTADOS', Lista::CARGO_DIPUTADOS);
        $this->assertEquals('SENADORES', Lista::CARGO_SENADORES);
        $this->assertIsString(Lista::CARGO_DIPUTADOS);
        $this->assertIsString(Lista::CARGO_SENADORES);
    }

    /** @test */
    public function test_cargo_constants_are_valid()
    {
        // Act & Assert
        $this->assertEquals('DIPUTADOS', 'DIPUTADOS');
        $this->assertEquals('SENADORES', 'SENADORES');
    }

    /** @test */
    public function test_belongs_to_provincia_relationship()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Assert
        $this->assertInstanceOf(Provincia::class, $lista->provincia);
        $this->assertEquals($provincia->id, $lista->provincia->id);
        $this->assertEquals($provincia->nombre, $lista->provincia->nombre);
    }

    /** @test */
    public function test_has_many_candidatos_relationship_ordered_by_orden()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidatos = Candidato::factory()->count(5)->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ])->sortBy('id'); // Order by ID to ensure proper ordering

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $lista->candidatos);
        $this->assertCount(5, $lista->candidatos);
        $this->assertTrue($lista->candidatos->contains($candidatos->first()));

        // Verify ordering (should be ordered by 'orden')
        $ordenes = $lista->candidatos->pluck('orden')->toArray();
        $sortedOrdenes = $ordenes;
        sort($sortedOrdenes);
        $this->assertEquals($sortedOrdenes, $ordenes);
    }

    /** @test */
    public function test_has_many_telegramas_relationship()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $telegramas = Telegrama::factory()->count(3)->create([
            'lista_id' => $lista->id
        ]);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $lista->telegramas);
        $this->assertCount(3, $lista->telegramas);
        $this->assertTrue($lista->telegramas->contains($telegramas->first()));
    }

    /** @test */
    public function test_candidatos_relationship_returns_empty_when_no_candidatos()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $candidatos = $lista->candidatos;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $candidatos);
        $this->assertCount(0, $candidatos);
    }

    /** @test */
    public function test_telegramas_relationship_returns_empty_when_no_telegramas()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $telegramas = $lista->telegramas;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $telegramas);
        $this->assertCount(0, $telegramas);
    }

    /** @test */
    public function test_cargo_must_be_diputados_or_senadores()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act & Assert - Valid cargos
        $listaDip = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);
        $this->assertEquals(Lista::CARGO_DIPUTADOS, $listaDip->cargo);

        $listaSen = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_SENADORES
        ]);
        $this->assertEquals(Lista::CARGO_SENADORES, $listaSen->cargo);
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

        // Act & Assert
        $this->assertNotNull($lista->created_at);
        $this->assertNotNull($lista->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $lista->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $lista->updated_at);
    }

    /** @test */
    public function test_casts_configuration()
    {
        // Arrange
        $model = new Lista();

        // Act & Assert
        $this->assertIsArray($model->getCasts());
    }

    /** @test */
    public function test_provincia_relationship_can_be_eager_loaded()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $listaWithProvincia = Lista::with('provincia')->find($lista->id);

        // Assert
        $this->assertTrue($listaWithProvincia->relationLoaded('provincia'));
        $this->assertInstanceOf(Provincia::class, $listaWithProvincia->provincia);
    }

    /** @test */
    public function test_candidatos_relationship_can_be_eager_loaded()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $lista = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);
        Candidato::factory()->count(3)->create([
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $listaWithCandidatos = Lista::with('candidatos')->find($lista->id);

        // Assert
        $this->assertTrue($listaWithCandidatos->relationLoaded('candidatos'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $listaWithCandidatos->candidatos);
        $this->assertCount(3, $listaWithCandidatos->candidatos);
    }

    /** @test */
    public function test_color_is_optional()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $lista1 = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'color' => null
        ]);

        $lista2 = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'color' => '#FF0000'
        ]);

        // Assert
        $this->assertNull($lista1->color);
        $this->assertEquals('#FF0000', $lista2->color);
    }

    /** @test */
    public function test_alianza_is_optional()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $lista1 = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'alianza' => null
        ]);

        $lista2 = Lista::factory()->create([
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS,
            'alianza' => 'Alianza Test'
        ]);

        // Assert
        $this->assertNull($lista1->alianza);
        $this->assertEquals('Alianza Test', $lista2->alianza);
    }
}
