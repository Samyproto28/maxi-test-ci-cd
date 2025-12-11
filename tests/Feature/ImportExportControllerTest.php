<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{Provincia, Lista, Mesa};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    // ===========================================
    // Tests para importarProvincias
    // ===========================================

    public function test_importar_provincias_csv_valido_retorna_200(): void
    {
        $csvContent = "nombre,codigo\nBuenos Aires,BA\nCórdoba,CBA\nSanta Fe,SF";
        $file = UploadedFile::fake()->createWithContent('provincias.csv', $csvContent);

        $response = $this->postJson('/api/v1/import/provincias', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'importados' => 3,
                'errores' => []
            ]);

        $this->assertDatabaseHas('provincias', ['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $this->assertDatabaseHas('provincias', ['nombre' => 'Córdoba', 'codigo' => 'CBA']);
        $this->assertDatabaseHas('provincias', ['nombre' => 'Santa Fe', 'codigo' => 'SF']);
    }

    public function test_importar_provincias_con_errores_retorna_207(): void
    {
        // Crear provincia existente para causar error de duplicado
        Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csvContent = "nombre,codigo\nCórdoba,CBA\nBuenos Aires,BA";
        $file = UploadedFile::fake()->createWithContent('provincias.csv', $csvContent);

        $response = $this->postJson('/api/v1/import/provincias', [
            'file' => $file
        ]);

        $response->assertStatus(207)
            ->assertJsonStructure([
                'success',
                'importados',
                'errores'
            ]);
    }

    public function test_importar_provincias_sin_archivo_retorna_422(): void
    {
        $response = $this->postJson('/api/v1/import/provincias', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_importar_provincias_archivo_invalido_retorna_422(): void
    {
        $file = UploadedFile::fake()->create('test.pdf', 100);

        $response = $this->postJson('/api/v1/import/provincias', [
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    // ===========================================
    // Tests para importarListas
    // ===========================================

    public function test_importar_listas_csv_valido_retorna_200(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csvContent = "nombre,alianza,provincia_id,cargo\nLista A,Alianza 1,{$provincia->id},DIPUTADOS\nLista B,Alianza 2,{$provincia->id},SENADORES";
        $file = UploadedFile::fake()->createWithContent('listas.csv', $csvContent);

        $response = $this->postJson('/api/v1/import/listas', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'importados' => 2,
            ]);

        $this->assertDatabaseHas('listas', ['nombre' => 'Lista A', 'cargo' => 'DIPUTADOS']);
    }

    public function test_importar_listas_provincia_inexistente_retorna_207(): void
    {
        $csvContent = "nombre,alianza,provincia_id,cargo\nLista A,Alianza 1,999,DIPUTADOS";
        $file = UploadedFile::fake()->createWithContent('listas.csv', $csvContent);

        $response = $this->postJson('/api/v1/import/listas', [
            'file' => $file
        ]);

        $response->assertStatus(207)
            ->assertJsonFragment(['importados' => 0]);
    }

    // ===========================================
    // Tests para importarCandidatos
    // ===========================================

    public function test_importar_candidatos_csv_valido_retorna_200(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);

        $csvContent = "nombre,lista_id,provincia_id,cargo,orden\nJuan Pérez,{$lista->id},{$provincia->id},DIPUTADOS,1";
        $file = UploadedFile::fake()->createWithContent('candidatos.csv', $csvContent);

        $response = $this->postJson('/api/v1/import/candidatos', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'importados' => 1,
            ]);

        $this->assertDatabaseHas('candidatos', ['nombre' => 'Juan Pérez', 'orden' => 1]);
    }

    // ===========================================
    // Tests para importarMesas
    // ===========================================

    public function test_importar_mesas_csv_valido_retorna_200(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csvContent = "id_mesa,provincia_id,circuito,establecimiento,electores\nMESA001,{$provincia->id},C1,Escuela N1,350";
        $file = UploadedFile::fake()->createWithContent('mesas.csv', $csvContent);

        $response = $this->postJson('/api/v1/import/mesas', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'importados' => 1,
            ]);

        $this->assertDatabaseHas('mesas', ['id_mesa' => 'MESA001', 'electores' => 350]);
    }

    // ===========================================
    // Tests para importarTelegramas
    // ===========================================

    public function test_importar_telegramas_csv_valido_retorna_200(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $mesa = Mesa::create(['id_mesa' => 'MESA001', 'provincia_id' => $provincia->id, 'circuito' => 'C1', 'establecimiento' => 'Escuela', 'electores' => 500]);

        $csvContent = "mesa_id,lista_id,votos_diputados,votos_senadores,blancos,nulos,recurridos\n{$mesa->id},{$lista->id},100,80,10,5,2";
        $file = UploadedFile::fake()->createWithContent('telegramas.csv', $csvContent);

        $response = $this->postJson('/api/v1/import/telegramas', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'importados' => 1,
            ]);

        $this->assertDatabaseHas('telegramas', [
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100
        ]);
    }

    public function test_importar_telegramas_votos_exceden_electores_retorna_207(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $mesa = Mesa::create(['id_mesa' => 'MESA001', 'provincia_id' => $provincia->id, 'circuito' => 'C1', 'establecimiento' => 'Escuela', 'electores' => 100]);

        // Total votos = 80+50+10+5+2 = 147 > 100 electores
        $csvContent = "mesa_id,lista_id,votos_diputados,votos_senadores,blancos,nulos,recurridos\n{$mesa->id},{$lista->id},80,50,10,5,2";
        $file = UploadedFile::fake()->createWithContent('telegramas.csv', $csvContent);

        $response = $this->postJson('/api/v1/import/telegramas', [
            'file' => $file
        ]);

        $response->assertStatus(207)
            ->assertJsonFragment(['importados' => 0]);
    }

    // ===========================================
    // Tests de validación de archivo
    // ===========================================

    public function test_archivo_mayor_10mb_es_rechazado(): void
    {
        // Crear archivo de 11MB
        $file = UploadedFile::fake()->create('large.csv', 11 * 1024); // 11MB

        $response = $this->postJson('/api/v1/import/provincias', [
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    // ===========================================
    // Tests para exportarResultadosProvinciales
    // ===========================================

    public function test_exportar_resultados_provinciales_descarga_csv(): void
    {
        // Setup: crear provincia, lista, mesa y telegrama
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create([
            'nombre' => 'Lista A',
            'alianza' => 'Alianza',
            'provincia_id' => $provincia->id,
            'cargo' => 'DIPUTADOS'
        ]);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA001',
            'provincia_id' => $provincia->id,
            'circuito' => 'C1',
            'establecimiento' => 'Escuela',
            'electores' => 500
        ]);

        // Crear telegrama con votos
        \App\Models\Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'votos_senadores' => 0,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 2,
            'usuario' => 'test_user'
        ]);

        $response = $this->get("/api/v1/export/provincial/{$provincia->id}?cargo=DIPUTADOS");

        $response->assertStatus(200)
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_exportar_resultados_provinciales_nombre_archivo_incluye_timestamp(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $response = $this->get("/api/v1/export/provincial/{$provincia->id}?cargo=DIPUTADOS");

        $response->assertStatus(200);

        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString("resultados_provincia_{$provincia->id}_DIPUTADOS_", $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }

    public function test_exportar_resultados_provinciales_sin_cargo_retorna_422(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $response = $this->getJson("/api/v1/export/provincial/{$provincia->id}");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }

    public function test_exportar_resultados_provinciales_cargo_invalido_retorna_422(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $response = $this->getJson("/api/v1/export/provincial/{$provincia->id}?cargo=INVALIDO");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }

    public function test_exportar_resultados_provinciales_senadores_funciona(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create([
            'nombre' => 'Lista B',
            'alianza' => 'Alianza B',
            'provincia_id' => $provincia->id,
            'cargo' => 'SENADORES'
        ]);
        $mesa = Mesa::create([
            'id_mesa' => 'MESA002',
            'provincia_id' => $provincia->id,
            'circuito' => 'C1',
            'establecimiento' => 'Escuela',
            'electores' => 500
        ]);

        \App\Models\Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 0,
            'votos_senadores' => 80,
            'blancos' => 5,
            'nulos' => 3,
            'recurridos' => 1,
            'usuario' => 'test_user'
        ]);

        $response = $this->get("/api/v1/export/provincial/{$provincia->id}?cargo=SENADORES");

        $response->assertStatus(200);
    }

    // ===========================================
    // Tests para exportarResumenNacional
    // ===========================================

    public function test_exportar_resumen_nacional_descarga_csv(): void
    {
        // Setup: crear múltiples provincias con datos
        $provincia1 = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $provincia2 = Provincia::create(['nombre' => 'Córdoba', 'codigo' => 'CBA']);

        $lista1 = Lista::create([
            'nombre' => 'Lista Nacional',
            'alianza' => 'Alianza',
            'provincia_id' => $provincia1->id,
            'cargo' => 'DIPUTADOS'
        ]);
        $lista2 = Lista::create([
            'nombre' => 'Lista Nacional',
            'alianza' => 'Alianza',
            'provincia_id' => $provincia2->id,
            'cargo' => 'DIPUTADOS'
        ]);

        $mesa1 = Mesa::create([
            'id_mesa' => 'MESA001',
            'provincia_id' => $provincia1->id,
            'circuito' => 'C1',
            'establecimiento' => 'Escuela',
            'electores' => 500
        ]);
        $mesa2 = Mesa::create([
            'id_mesa' => 'MESA002',
            'provincia_id' => $provincia2->id,
            'circuito' => 'C1',
            'establecimiento' => 'Escuela',
            'electores' => 500
        ]);

        \App\Models\Telegrama::create([
            'mesa_id' => $mesa1->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 100,
            'votos_senadores' => 0,
            'blancos' => 10,
            'nulos' => 5,
            'recurridos' => 2,
            'usuario' => 'test_user'
        ]);
        \App\Models\Telegrama::create([
            'mesa_id' => $mesa2->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 80,
            'votos_senadores' => 0,
            'blancos' => 8,
            'nulos' => 3,
            'recurridos' => 1,
            'usuario' => 'test_user'
        ]);

        $response = $this->get('/api/v1/export/nacional?cargo=DIPUTADOS');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_exportar_resumen_nacional_nombre_archivo_incluye_timestamp(): void
    {
        $response = $this->get('/api/v1/export/nacional?cargo=SENADORES');

        $response->assertStatus(200);

        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('resumen_nacional_SENADORES_', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }

    public function test_exportar_resumen_nacional_sin_cargo_retorna_422(): void
    {
        $response = $this->getJson('/api/v1/export/nacional');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }

    public function test_exportar_resumen_nacional_cargo_invalido_retorna_422(): void
    {
        $response = $this->getJson('/api/v1/export/nacional?cargo=PRESIDENTE');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cargo']);
    }
}
