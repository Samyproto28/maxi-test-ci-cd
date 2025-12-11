<?php

namespace Tests\Unit\Models;

use App\Models\Telegrama;
use App\Models\Mesa;
use App\Models\Lista;
use App\Models\Provincia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_table_name_is_telegramas()
    {
        // Act & Assert
        $this->assertEquals('telegramas', (new Telegrama)->getTable());
    }

    /** @test */
    public function test_fillable_attributes()
    {
        // Arrange
        $fillable = [
            'mesa_id',
            'lista_id',
            'votos_diputados',
            'votos_senadores',
            'blancos',
            'nulos',
            'recurridos',
            'usuario',
            'user_id'
        ];

        // Act & Assert
        $this->assertEquals($fillable, (new Telegrama)->getFillable());
    }

    /** @test */
    public function test_casts_configuration()
    {
        // Arrange
        $model = new Telegrama();

        // Act & Assert
        $this->assertIsArray($model->getCasts());
    }

    /** @test */
    public function test_belongs_to_mesa_relationship()
    {
        // Arrange
        $mesa = Mesa::factory()->create();

        // Act
        $telegrama = Telegrama::factory()->create([
            'mesa_id' => $mesa->id
        ]);

        // Assert
        $this->assertInstanceOf(Mesa::class, $telegrama->mesa);
        $this->assertEquals($mesa->id, $telegrama->mesa->id);
        $this->assertEquals($mesa->id_mesa, $telegrama->mesa->id_mesa);
    }

    /** @test */
    public function test_belongs_to_lista_relationship()
    {
        // Arrange
        $lista = Lista::factory()->create();

        // Act
        $telegrama = Telegrama::factory()->create([
            'lista_id' => $lista->id
        ]);

        // Assert
        $this->assertInstanceOf(Lista::class, $telegrama->lista);
        $this->assertEquals($lista->id, $telegrama->lista->id);
        $this->assertEquals($lista->nombre, $telegrama->lista->nombre);
    }

    /** @test */
    public function test_total_votos_method()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 100,
            'votos_senadores' => 50,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3
        ]);

        // Act
        $total = $telegrama->totalVotos();

        // Assert
        $expected = 100 + 50 + 10 + 5 + 3; // 168
        $this->assertEquals($expected, $total);
    }

    /** @test */
    public function test_total_votos_with_only_diputados()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 150,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $total = $telegrama->totalVotos();

        // Assert
        $this->assertEquals(150, $total);
    }

    /** @test */
    public function test_total_votos_with_only_senadores()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 0,
            'votos_senadores' => 120,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $total = $telegrama->totalVotos();

        // Assert
        $this->assertEquals(120, $total);
    }

    /** @test */
    public function test_total_votos_with_zeros()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 0,
            'votos_senadores' => 0,
            'blancos' => 0,
            'nulos' => 0,
            'recurridos' => 0
        ]);

        // Act
        $total = $telegrama->totalVotos();

        // Assert
        $this->assertEquals(0, $total);
    }

    /** @test */
    public function test_total_votos_with_all_types()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 200,
            'votos_senadores' => 180,
            'blancos' => 25,
            'nulos' => 15,
            'recurridos' => 10
        ]);

        // Act
        $total = $telegrama->totalVotos();

        // Assert
        $this->assertEquals(430, $total);
    }

    /** @test */
    public function test_vote_fields_are_integers()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 100,
            'votos_senadores' => 50,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3
        ]);

        // Act & Assert
        $this->assertIsInt($telegrama->votos_diputados);
        $this->assertIsInt($telegrama->votos_senadores);
        $this->assertIsInt($telegrama->blancos);
        $this->assertIsInt($telegrama->nulos);
        $this->assertIsInt($telegrama->recurridos);
    }

    /** @test */
    public function test_vote_fields_are_non_negative()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 100,
            'votos_senadores' => 50,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3
        ]);

        // Act & Assert
        $this->assertGreaterThanOrEqual(0, $telegrama->votos_diputados);
        $this->assertGreaterThanOrEqual(0, $telegrama->votos_senadores);
        $this->assertGreaterThanOrEqual(0, $telegrama->blancos);
        $this->assertGreaterThanOrEqual(0, $telegrama->nulos);
        $this->assertGreaterThanOrEqual(0, $telegrama->recurridos);
    }

    /** @test */
    public function test_usuario_is_required_string()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'usuario' => 'test_user'
        ]);

        // Act & Assert
        $this->assertIsString($telegrama->usuario);
        $this->assertEquals('test_user', $telegrama->usuario);
    }

    /** @test */
    public function test_usuario_is_required()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'usuario' => 'test_user'
        ]);

        // Act & Assert
        $this->assertNotNull($telegrama->usuario);
        $this->assertEquals('test_user', $telegrama->usuario);
    }

    /** @test */
    public function test_user_id_is_optional()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'user_id' => null
        ]);

        // Act & Assert
        $this->assertNull($telegrama->user_id);
    }

    /** @test */
    public function test_has_correct_timestamps()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create();

        // Act & Assert
        $this->assertNotNull($telegrama->created_at);
        $this->assertNotNull($telegrama->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $telegrama->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $telegrama->updated_at);
    }

    /** @test */
    public function test_mesa_relationship_can_be_eager_loaded()
    {
        // Arrange
        $mesa = Mesa::factory()->create();

        // Act
        $telegrama = Telegrama::factory()->create([
            'mesa_id' => $mesa->id
        ]);

        $telegramaWithMesa = Telegrama::with('mesa')->find($telegrama->id);

        // Assert
        $this->assertTrue($telegramaWithMesa->relationLoaded('mesa'));
        $this->assertInstanceOf(Mesa::class, $telegramaWithMesa->mesa);
    }

    /** @test */
    public function test_lista_relationship_can_be_eager_loaded()
    {
        // Arrange
        $lista = Lista::factory()->create();

        // Act
        $telegrama = Telegrama::factory()->create([
            'lista_id' => $lista->id
        ]);

        $telegramaWithLista = Telegrama::with('lista')->find($telegrama->id);

        // Assert
        $this->assertTrue($telegramaWithLista->relationLoaded('lista'));
        $this->assertInstanceOf(Lista::class, $telegramaWithLista->lista);
    }

    /** @test */
    public function test_can_create_telegrama_with_minimal_data()
    {
        // Arrange
        $mesa = Mesa::factory()->create();
        $lista = Lista::factory()->create(['provincia_id' => $mesa->provincia_id]);

        // Act
        $telegrama = Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 50,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3,
            'usuario' => 'test_user'
        ]);

        // Assert
        $this->assertDatabaseHas('telegramas', [
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 50,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3,
            'usuario' => 'test_user'
        ]);
    }

    /** @test */
    public function test_total_votos_calculates_correctly_with_large_numbers()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 500,
            'votos_senadores' => 450,
            'blancos' => 100,
            'nulos' => 75,
            'recurridos' => 50
        ]);

        // Act
        $total = $telegrama->totalVotos();

        // Assert
        $this->assertEquals(1175, $total);
    }

    /** @test */
    public function test_mesa_and_lista_relationships_are_correct()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        $lista = Lista::factory()->create(['provincia_id' => $provincia->id]);

        // Act
        $telegrama = Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id
        ]);

        // Assert
        $this->assertEquals($mesa->id, $telegrama->mesa->id);
        $this->assertEquals($lista->id, $telegrama->lista->id);
        $this->assertEquals($provincia->id, $telegrama->mesa->provincia->id);
        $this->assertEquals($provincia->id, $telegrama->lista->provincia->id);
    }

    /** @test */
    public function test_telegrama_belongs_to_same_provincia_as_mesa_and_lista()
    {
        // Arrange
        $provincia = Provincia::factory()->create();
        $mesa = Mesa::factory()->create(['provincia_id' => $provincia->id]);
        $lista = Lista::factory()->create(['provincia_id' => $provincia->id]);

        // Act
        $telegrama = Telegrama::factory()->create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id
        ]);

        // Assert
        $this->assertEquals($provincia->id, $telegrama->mesa->provincia_id);
        $this->assertEquals($provincia->id, $telegrama->lista->provincia_id);
        $this->assertEquals(
            $telegrama->mesa->provincia_id,
            $telegrama->lista->provincia_id
        );
    }

    /** @test */
    public function test_vote_fields_cast_to_integer()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => '100',
            'votos_senadores' => '50',
            'blancos' => '10',
            'nulos' => '5',
            'recurridos' => '3'
        ]);

        // Act & Assert
        $this->assertIsInt($telegrama->votos_diputados);
        $this->assertIsInt($telegrama->votos_senadores);
        $this->assertIsInt($telegrama->blancos);
        $this->assertIsInt($telegrama->nulos);
        $this->assertIsInt($telegrama->recurridos);
    }

    /** @test */
    public function test_total_votos_returns_integer()
    {
        // Arrange
        $telegrama = Telegrama::factory()->create([
            'votos_diputados' => 100,
            'votos_senadores' => 50,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 3
        ]);

        // Act
        $total = $telegrama->totalVotos();

        // Assert
        $this->assertIsInt($total);
        $this->assertEquals(168, $total);
    }
}
