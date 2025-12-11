<?php

namespace Tests\Feature;

use App\Models\{Provincia, Lista, Mesa, Telegrama, Candidato, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use OwenIt\Auditing\Models\Audit;

class AuditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure migrations are run
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_audits_telegrama_creation()
    {
        // Create required data
        $provincia = Provincia::factory()->create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $lista = Lista::factory()->create([
            'nombre' => 'Lista 1',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'electores' => 300
        ]);

        // Create telegrama
        $telegrama = Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 150,
            'votos_senadores' => 145,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 2,
            'usuario' => 'test_user'
        ]);

        // Verify audit record was created
        $this->assertDatabaseHas('audits', [
            'auditable_type' => Telegrama::class,
            'auditable_id' => $telegrama->id,
            'event' => 'created',
            'user_id' => null, // No authenticated user in this test
            'user_type' => null
        ]);

        // Verify the telegrama has the audits relationship
        $this->assertTrue($telegrama->audits()->exists());
        $this->assertCount(1, $telegrama->audits);
    }

    /** @test */
    public function it_audits_telegrama_update()
    {
        // Create required data
        $provincia = Provincia::factory()->create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $lista = Lista::factory()->create([
            'nombre' => 'Lista 1',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'electores' => 300
        ]);

        // Create telegrama
        $telegrama = Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 150,
            'votos_senadores' => 145,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 2,
            'usuario' => 'test_user'
        ]);

        // Update telegrama
        $telegrama->update([
            'votos_diputados' => 160,
            'votos_senadores' => 155
        ]);

        // Verify audit record was created
        $audit = $telegrama->audits()->where('event', 'updated')->first();

        $this->assertNotNull($audit);
        $this->assertEquals('updated', $audit->event);
        $this->assertNotNull($audit->old_values);
        $this->assertNotNull($audit->new_values);
        $this->assertEquals(150, $audit->old_values['votos_diputados']);
        $this->assertEquals(160, $audit->new_values['votos_diputados']);
    }

    /** @test */
    public function it_audits_telegrama_deletion()
    {
        // Create required data
        $provincia = Provincia::factory()->create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $lista = Lista::factory()->create([
            'nombre' => 'Lista 1',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'electores' => 300
        ]);

        // Create telegrama
        $telegrama = Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 150,
            'votos_senadores' => 145,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 2,
            'usuario' => 'test_user'
        ]);

        $telegramaId = $telegrama->id;

        // Delete telegrama
        $telegrama->delete();

        // Verify audit record was created
        $this->assertDatabaseHas('audits', [
            'auditable_type' => Telegrama::class,
            'auditable_id' => $telegramaId,
            'event' => 'deleted'
        ]);

        // Verify old_values are preserved
        $audit = Audit::where('auditable_id', $telegramaId)->where('event', 'deleted')->first();
        $this->assertNotNull($audit->old_values);
        $this->assertEquals(150, $audit->old_values['votos_diputados']);
    }

    /** @test */
    public function it_audits_candidato_creation()
    {
        // Create required data
        $provincia = Provincia::factory()->create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $lista = Lista::factory()->create([
            'nombre' => 'Lista 1',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Create candidato
        $candidato = Candidato::create([
            'nombre' => 'Juan Pérez',
            'lista_id' => $lista->id,
            'provincia_id' => $provincia->id,
            'cargo' => Candidato::CARGO_DIPUTADOS,
            'orden' => 1
        ]);

        // Verify audit record was created
        $this->assertDatabaseHas('audits', [
            'auditable_type' => Candidato::class,
            'auditable_id' => $candidato->id,
            'event' => 'created'
        ]);
    }

    /** @test */
    public function it_audits_lista_creation()
    {
        // Create required data
        $provincia = Provincia::factory()->create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        // Create lista
        $lista = Lista::create([
            'nombre' => 'Lista 1',
            'alianza' => 'Alianza A',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        // Verify audit record was created
        $this->assertDatabaseHas('audits', [
            'auditable_type' => Lista::class,
            'auditable_id' => $lista->id,
            'event' => 'created'
        ]);
    }

    /** @test */
    public function it_can_retrieve_audits_for_model()
    {
        // Create required data
        $provincia = Provincia::factory()->create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $lista = Lista::factory()->create([
            'nombre' => 'Lista 1',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'electores' => 300
        ]);

        // Create and update telegrama
        $telegrama = Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 150,
            'votos_senadores' => 145,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 2,
            'usuario' => 'test_user'
        ]);

        $telegrama->update(['votos_diputados' => 160]);

        // Retrieve audits
        $audits = $telegrama->audits;

        // Verify
        $this->assertCount(2, $audits);
        $this->assertEquals('created', $audits->first()->event);
        $this->assertEquals('updated', $audits->last()->event);
    }

    /** @test */
    public function it_audits_with_authenticated_user()
    {
        // Create user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        // Create required data
        $provincia = Provincia::factory()->create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        $lista = Lista::factory()->create([
            'nombre' => 'Lista 1',
            'provincia_id' => $provincia->id,
            'cargo' => Lista::CARGO_DIPUTADOS
        ]);

        $mesa = Mesa::factory()->create([
            'provincia_id' => $provincia->id,
            'electores' => 300
        ]);

        // Act as authenticated user
        $this->actingAs($user);

        // Create telegrama
        $telegrama = Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 150,
            'votos_senadores' => 145,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 2,
            'usuario' => 'test_user'
        ]);

        // Verify audit record has user information
        $audit = $telegrama->audits()->first();

        $this->assertNotNull($audit->user_id);
        $this->assertEquals($user->id, $audit->user_id);
        $this->assertEquals(User::class, $audit->user_type);
    }
}
