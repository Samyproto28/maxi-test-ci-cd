<?php

namespace Tests\Unit\Models;

use App\Models\Mesa;
use App\Models\Provincia;
use App\Models\Telegrama;
use App\Models\Lista;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_table_name_is_mesas()
    {
        // Act & Assert
        $this->assertEquals('mesas', (new Mesa)->getTable());
    }

    /** @test */
    public function test_fillable_attributes()
    {
        // Arrange
        $fillable = [
            'id_mesa',
            'circuito',
            'establecimiento',
            'electores',
            'provincia_id'
        ];

        // Act & Assert
        $this->assertEquals($fillable, (new Mesa)->getFillable());
    }

    /** @test */
    public function test_casts_configuration()
    {
        // Arrange
        $model = new Mesa();

        // Act & Assert
        $this->assertIsArray($model->getCasts());
    }

    /** @test */
    public function test_belongs_to_provincia_relationship()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id
        ]);

        // Assert
        $this->assertInstanceOf(Provincia::class, $mesa->provincia);
        $this->assertEquals($provincia->id, $mesa->provincia->id);
        $this->assertEquals($provincia->nombre, $mesa->provincia->nombre);
    }

    /** @test */
    public function test_has_many_telegramas_relationship()
    {
        // Arrange
        $mesa = Mesa::factory()->create();

        // Act
        $telegramas = Telegrama::factory()->count(3)->create([
            'mesa_id' => $mesa->id
        ]);

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $mesa->telegramas);
        $this->assertCount(3, $mesa->telegramas);
        $this->assertTrue($mesa->telegramas->contains($telegramas->first()));
    }

    /** @test */
    public function test_telegramas_relationship_returns_empty_when_no_telegramas()
    {
        // Arrange
        $mesa = Mesa::factory()->create();

        // Act
        $telegramas = $mesa->telegramas;

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $telegramas);
        $this->assertCount(0, $telegramas);
    }

    /** @test */
    public function test_total_votos_cargados_helper_method()
    {
        // Arrange
        $mesa = Mesa::factory()->create();
        $lista = Lista::factory()->create(['provincia_id' => $mesa->provincia_id]);

        // Act
        $telegrama1 = Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 50,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3
        ]);

        // Assert
        $expected = 100 + 50 + 10 + 5 + 3; // 168
        $this->assertEquals($expected, $mesa->totalVotosCargados());
    }

    /** @test */
    public function test_total_votos_cargados_with_multiple_telegramas()
    {
        // Arrange
        $mesa = Mesa::factory()->create();
        $lista1 = Lista::factory()->create(['provincia_id' => $mesa->provincia_id]);
        $lista2 = Lista::factory()->create(['provincia_id' => $mesa->provincia_id]);

        // Act
        $telegrama1 = Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 100,
            'votos_senadores' => 50,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3
        ]);

        $telegrama2 = Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 80,
            'votos_senadores' => 40,
            'blancos' => 8,
            'nulos' => 4,
            'recurridos' => 2
        ]);

        // Assert
        $expected = (100 + 50 + 10 + 5 + 3) + (80 + 40 + 8 + 4 + 2); // 302
        $this->assertEquals($expected, $mesa->totalVotosCargados());
    }

    /** @test */
    public function test_total_votos_cargados_returns_zero_when_no_telegramas()
    {
        // Arrange
        $mesa = Mesa::factory()->create();

        // Act
        $total = $mesa->totalVotosCargados();

        // Assert
        $this->assertEquals(0, $total);
    }

    /** @test */
    public function test_total_votos_cargados_with_only_diputados()
    {
        // Arrange
        $mesa = Mesa::factory()->create();
        $listaDip = Lista::factory()->create([
            'provincia_id' => $mesa->provincia_id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Act
        $telegrama = Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $listaDip->id,
            'votos_diputados' => 150,
            'votos_senadores' => 0,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3
        ]);

        // Assert
        $expected = 150 + 0 + 10 + 5 + 3; // 168
        $this->assertEquals($expected, $mesa->totalVotosCargados());
    }

    /** @test */
    public function test_total_votos_cargados_with_only_senadores()
    {
        // Arrange
        $mesa = Mesa::factory()->create();
        $listaSen = Lista::factory()->create([
            'provincia_id' => $mesa->provincia_id,
            'cargo' => Lista::CARGO_SENADORES
        ]);

        // Act
        $telegrama = Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $listaSen->id,
            'votos_diputados' => 0,
            'votos_senadores' => 120,
            'blancos' => 8,
            'nulos' => 4,
            'recurridos' => 2
        ]);

        // Assert
        $expected = 0 + 120 + 8 + 4 + 2; // 134
        $this->assertEquals($expected, $mesa->totalVotosCargados());
    }

    /** @test */
    public function test_id_mesa_is_unique_per_provincia()
    {
        // Arrange
        $provincia1 = Provincia::factory()->create();
        $provincia2 = Provincia::factory()->create();

        // Act
        $mesa1 = Mesa::factory()->create([
            'provincia_id' => $provincia1->id,
            'id_mesa' => 'MESA-001'
        ]);

        $mesa2 = Mesa::factory()->create([
            'provincia_id' => $provincia2->id,
            'id_mesa' => 'MESA-001' // Same ID, different provincia - should be allowed
        ]);

        // Assert
        $this->assertDatabaseHas('mesas', [
            'id' => $mesa1->id,
            'provincia_id' => $provincia1->id,
            'id_mesa' => 'MESA-001'
        ]);

        $this->assertDatabaseHas('mesas', [
            'id' => $mesa2->id,
            'provincia_id' => $provincia2->id,
            'id_mesa' => 'MESA-001'
        ]);

        $this->assertNotEquals($mesa1->id, $mesa2->id);
    }

    /** @test */
    public function test_electores_must_be_positive_integer()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'electores' => 350
        ]);

        // Assert
        $this->assertIsInt($mesa->electores);
        $this->assertGreaterThan(0, $mesa->electores);
        $this->assertEquals(350, $mesa->electores);
    }

    /** @test */
    public function test_circuito_is_optional()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesa1 = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'circuito' => null
        ]);

        $mesa2 = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'circuito' => '001'
        ]);

        // Assert
        $this->assertNull($mesa1->circuito);
        $this->assertEquals('001', $mesa2->circuito);
    }

    /** @test */
    public function test_establecimiento_is_optional()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesa1 = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'establecimiento' => null
        ]);

        $mesa2 = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'establecimiento' => 'Escuela Nacional'
        ]);

        // Assert
        $this->assertNull($mesa1->establecimiento);
        $this->assertEquals('Escuela Nacional', $mesa2->establecimiento);
    }

    /** @test */
    public function test_has_correct_timestamps()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id
        ]);

        // Assert
        $this->assertNotNull($mesa->created_at);
        $this->assertNotNull($mesa->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $mesa->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $mesa->updated_at);
    }

    /** @test */
    public function test_provincia_relationship_can_be_eager_loaded()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id
        ]);

        $mesaWithProvincia = Mesa::with('provincia')->find($mesa->id);

        // Assert
        $this->assertTrue($mesaWithProvincia->relationLoaded('provincia'));
        $this->assertInstanceOf(Provincia::class, $mesaWithProvincia->provincia);
    }

    /** @test */
    public function test_telegramas_relationship_can_be_eager_loaded()
    {
        // Arrange
        $mesa = Mesa::factory()->create();

        // Act
        Telegrama::factory()->count(2)->create([
            'mesa_id' => $mesa->id
        ]);

        $mesaWithTelegramas = Mesa::with('telegramas')->find($mesa->id);

        // Assert
        $this->assertTrue($mesaWithTelegramas->relationLoaded('telegramas'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $mesaWithTelegramas->telegramas);
        $this->assertCount(2, $mesaWithTelegramas->telegramas);
    }

    /** @test */
    public function test_can_create_mesa_with_minimal_data()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesa = Mesa::create([
            'id_mesa' => 'MESA-TEST',
            'provincia_id' => $provincia->id,
            'electores' => 300
        ]);

        // Assert
        $this->assertDatabaseHas('mesas', [
            'id_mesa' => 'MESA-TEST',
            'provincia_id' => $provincia->id,
            'electores' => 300
        ]);
        $this->assertEquals('MESA-TEST', $mesa->id_mesa);
        $this->assertEquals(300, $mesa->electores);
    }

    /** @test */
    public function test_id_mesa_is_string()
    {
        // Arrange
        $provincia = Provincia::factory()->create();

        // Act
        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'id_mesa' => 'MESA-123'
        ]);

        // Assert
        $this->assertIsString($mesa->id_mesa);
        $this->assertEquals('MESA-123', $mesa->id_mesa);
    }

    /** @test */
    public function test_electores_calculates_percentage_loaded()
    {
        // Arrange
        $mesa = Mesa::factory()->create(['electores' => 1000]);
        $lista = Lista::factory()->create(['provincia_id' => $mesa->provincia_id]);

        // Act
        Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 300,
            'votos_senadores' => 200,
            'blancos' => 50,
            'nulos' => 30,
            'recurridos' => 20
        ]);

        $totalVotos = $mesa->totalVotosCargados();

        // Assert
        $this->assertEquals(600, $totalVotos);
        $this->assertEquals(60.0, ($totalVotos / $mesa->electores) * 100);
    }
}
