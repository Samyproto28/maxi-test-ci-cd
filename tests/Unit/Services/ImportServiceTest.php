<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ImportService;
use App\Services\TelegramaValidationService;
use App\Models\{Provincia, Lista, Candidato, Mesa, Telegrama};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class ImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImportService $service;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImportService(new TelegramaValidationService());
        $this->tempDir = sys_get_temp_dir() . '/import_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // Limpiar archivos temporales
        array_map('unlink', glob("{$this->tempDir}/*"));
        rmdir($this->tempDir);
        parent::tearDown();
    }

    private function createTempCsv(string $filename, string $content): string
    {
        $path = "{$this->tempDir}/{$filename}";
        file_put_contents($path, $content);
        return $path;
    }

    // ===========================================
    // Tests para importarProvincias
    // ===========================================

    public function test_importar_provincias_csv_valido(): void
    {
        $csv = $this->createTempCsv('provincias.csv', "nombre,codigo\nBuenos Aires,BA\nCórdoba,CBA\nSanta Fe,SF\nMendoza,MZA\nTucumán,TUC");

        $resultado = $this->service->importarProvincias($csv);

        $this->assertEquals(5, $resultado['importados']);
        $this->assertEmpty($resultado['errores']);
        $this->assertEquals(5, Provincia::count());
        $this->assertDatabaseHas('provincias', ['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
    }

    public function test_importar_provincias_con_error_en_linea_especifica(): void
    {
        // Crear una provincia existente para causar error de duplicado
        Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csv = $this->createTempCsv('provincias.csv', "nombre,codigo\nCórdoba,CBA\nBuenos Aires,BA\nSanta Fe,SF");

        $resultado = $this->service->importarProvincias($csv);

        $this->assertEquals(2, $resultado['importados']);
        $this->assertCount(1, $resultado['errores']);
        $this->assertStringContainsString('Línea 3', $resultado['errores'][0]);
    }

    public function test_importar_provincias_campos_faltantes(): void
    {
        $csv = $this->createTempCsv('provincias.csv', "nombre,codigo\nBuenos Aires,BA\n,CBA\nSanta Fe,");

        $resultado = $this->service->importarProvincias($csv);

        $this->assertEquals(1, $resultado['importados']);
        $this->assertCount(2, $resultado['errores']);
        $this->assertStringContainsString("campo 'nombre' es requerido", $resultado['errores'][0]);
        $this->assertStringContainsString("campo 'codigo' es requerido", $resultado['errores'][1]);
    }

    public function test_importar_provincias_headers_faltantes(): void
    {
        $csv = $this->createTempCsv('provincias.csv', "nombre\nBuenos Aires\nCórdoba");

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Headers faltantes');

        $this->service->importarProvincias($csv);
    }

    public function test_importar_provincias_archivo_no_existe(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El archivo no existe');

        $this->service->importarProvincias('/ruta/inexistente.csv');
    }

    public function test_importar_provincias_estructura_retorno_correcta(): void
    {
        $csv = $this->createTempCsv('provincias.csv', "nombre,codigo\nBuenos Aires,BA");

        $resultado = $this->service->importarProvincias($csv);

        $this->assertArrayHasKey('importados', $resultado);
        $this->assertArrayHasKey('errores', $resultado);
        $this->assertIsInt($resultado['importados']);
        $this->assertIsArray($resultado['errores']);
    }

    // ===========================================
    // Tests para importarListas
    // ===========================================

    public function test_importar_listas_csv_valido(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csv = $this->createTempCsv('listas.csv', "nombre,alianza,provincia_id,cargo\nLista A,Alianza 1,{$provincia->id},DIPUTADOS\nLista B,Alianza 2,{$provincia->id},SENADORES");

        $resultado = $this->service->importarListas($csv);

        $this->assertEquals(2, $resultado['importados']);
        $this->assertEmpty($resultado['errores']);
        $this->assertDatabaseHas('listas', ['nombre' => 'Lista A', 'cargo' => 'DIPUTADOS']);
    }

    public function test_importar_listas_provincia_inexistente(): void
    {
        $csv = $this->createTempCsv('listas.csv', "nombre,alianza,provincia_id,cargo\nLista A,Alianza 1,999,DIPUTADOS");

        $resultado = $this->service->importarListas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertCount(1, $resultado['errores']);
        $this->assertStringContainsString("No existe registro con ID 999", $resultado['errores'][0]);
    }

    public function test_importar_listas_cargo_invalido(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csv = $this->createTempCsv('listas.csv', "nombre,alianza,provincia_id,cargo\nLista A,Alianza 1,{$provincia->id},INVALIDO");

        $resultado = $this->service->importarListas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString('Cargo inválido', $resultado['errores'][0]);
    }

    // ===========================================
    // Tests para importarCandidatos
    // ===========================================

    public function test_importar_candidatos_csv_valido(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);

        $csv = $this->createTempCsv('candidatos.csv', "nombre,lista_id,provincia_id,cargo,orden\nJuan Pérez,{$lista->id},{$provincia->id},DIPUTADOS,1\nMaría García,{$lista->id},{$provincia->id},DIPUTADOS,2");

        $resultado = $this->service->importarCandidatos($csv);

        $this->assertEquals(2, $resultado['importados']);
        $this->assertEmpty($resultado['errores']);
        $this->assertDatabaseHas('candidatos', ['nombre' => 'Juan Pérez', 'orden' => 1]);
    }

    public function test_importar_candidatos_lista_inexistente(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csv = $this->createTempCsv('candidatos.csv', "nombre,lista_id,provincia_id,cargo,orden\nJuan Pérez,999,{$provincia->id},DIPUTADOS,1");

        $resultado = $this->service->importarCandidatos($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString("No existe registro con ID 999", $resultado['errores'][0]);
    }

    public function test_importar_candidatos_orden_negativo(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);

        $csv = $this->createTempCsv('candidatos.csv', "nombre,lista_id,provincia_id,cargo,orden\nJuan Pérez,{$lista->id},{$provincia->id},DIPUTADOS,-1");

        $resultado = $this->service->importarCandidatos($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString("entero positivo", $resultado['errores'][0]);
    }

    // ===========================================
    // Tests para importarMesas
    // ===========================================

    public function test_importar_mesas_csv_valido(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csv = $this->createTempCsv('mesas.csv', "id_mesa,provincia_id,circuito,establecimiento,electores\nMESA001,{$provincia->id},C1,Escuela N1,350\nMESA002,{$provincia->id},C2,Escuela N2,400");

        $resultado = $this->service->importarMesas($csv);

        $this->assertEquals(2, $resultado['importados']);
        $this->assertEmpty($resultado['errores']);
        $this->assertDatabaseHas('mesas', ['id_mesa' => 'MESA001', 'electores' => 350]);
    }

    public function test_importar_mesas_provincia_inexistente(): void
    {
        $csv = $this->createTempCsv('mesas.csv', "id_mesa,provincia_id,circuito,establecimiento,electores\nMESA001,999,C1,Escuela N1,350");

        $resultado = $this->service->importarMesas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString("No existe registro con ID 999", $resultado['errores'][0]);
    }

    public function test_importar_mesas_electores_negativo(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csv = $this->createTempCsv('mesas.csv', "id_mesa,provincia_id,circuito,establecimiento,electores\nMESA001,{$provincia->id},C1,Escuela N1,-100");

        $resultado = $this->service->importarMesas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString("entero positivo", $resultado['errores'][0]);
    }

    public function test_importar_mesas_electores_no_numerico(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);

        $csv = $this->createTempCsv('mesas.csv', "id_mesa,provincia_id,circuito,establecimiento,electores\nMESA001,{$provincia->id},C1,Escuela N1,abc");

        $resultado = $this->service->importarMesas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString("entero positivo", $resultado['errores'][0]);
    }

    // ===========================================
    // Tests para importarTelegramas
    // ===========================================

    public function test_importar_telegramas_csv_valido(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $mesa = Mesa::create(['id_mesa' => 'MESA001', 'provincia_id' => $provincia->id, 'circuito' => 'C1', 'establecimiento' => 'Escuela', 'electores' => 500]);

        $csv = $this->createTempCsv('telegramas.csv', "mesa_id,lista_id,votos_diputados,votos_senadores,blancos,nulos,recurridos\n{$mesa->id},{$lista->id},100,80,10,5,2");

        $resultado = $this->service->importarTelegramas($csv, 'admin');

        $this->assertEquals(1, $resultado['importados']);
        $this->assertEmpty($resultado['errores']);
        $this->assertDatabaseHas('telegramas', [
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 100,
            'usuario' => 'admin'
        ]);
    }

    public function test_importar_telegramas_votos_exceden_electores(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $mesa = Mesa::create(['id_mesa' => 'MESA001', 'provincia_id' => $provincia->id, 'circuito' => 'C1', 'establecimiento' => 'Escuela', 'electores' => 100]);

        $csv = $this->createTempCsv('telegramas.csv', "mesa_id,lista_id,votos_diputados,votos_senadores,blancos,nulos,recurridos\n{$mesa->id},{$lista->id},80,50,10,5,2");

        $resultado = $this->service->importarTelegramas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertCount(1, $resultado['errores']);
        $this->assertStringContainsString('excede la cantidad de electores', $resultado['errores'][0]);
    }

    public function test_importar_telegramas_duplicado(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $mesa = Mesa::create(['id_mesa' => 'MESA001', 'provincia_id' => $provincia->id, 'circuito' => 'C1', 'establecimiento' => 'Escuela', 'electores' => 500]);

        // Crear telegrama existente
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 50,
            'votos_senadores' => 40,
            'blancos' => 5,
            'nulos' => 3,
            'recurridos' => 1,
            'usuario' => 'test_user'
        ]);

        $csv = $this->createTempCsv('telegramas.csv', "mesa_id,lista_id,votos_diputados,votos_senadores,blancos,nulos,recurridos\n{$mesa->id},{$lista->id},100,80,10,5,2");

        $resultado = $this->service->importarTelegramas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString('Ya existe un telegrama', $resultado['errores'][0]);
    }

    public function test_importar_telegramas_votos_negativos(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);
        $mesa = Mesa::create(['id_mesa' => 'MESA001', 'provincia_id' => $provincia->id, 'circuito' => 'C1', 'establecimiento' => 'Escuela', 'electores' => 500]);

        $csv = $this->createTempCsv('telegramas.csv', "mesa_id,lista_id,votos_diputados,votos_senadores,blancos,nulos,recurridos\n{$mesa->id},{$lista->id},-10,80,10,5,2");

        $resultado = $this->service->importarTelegramas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString('no puede ser negativo', $resultado['errores'][0]);
    }

    public function test_importar_telegramas_mesa_inexistente(): void
    {
        $provincia = Provincia::create(['nombre' => 'Buenos Aires', 'codigo' => 'BA']);
        $lista = Lista::create(['nombre' => 'Lista A', 'alianza' => 'Alianza', 'provincia_id' => $provincia->id, 'cargo' => 'DIPUTADOS']);

        $csv = $this->createTempCsv('telegramas.csv', "mesa_id,lista_id,votos_diputados,votos_senadores,blancos,nulos,recurridos\n999,{$lista->id},100,80,10,5,2");

        $resultado = $this->service->importarTelegramas($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertStringContainsString("No existe registro con ID 999", $resultado['errores'][0]);
    }

    // ===========================================
    // Tests de encoding UTF-8
    // ===========================================

    public function test_importar_con_caracteres_especiales_utf8(): void
    {
        $csv = $this->createTempCsv('provincias.csv', "nombre,codigo\nCórdoba,CBA\nSão Paulo,SP\nÑuñoa,ÑÑ");

        $resultado = $this->service->importarProvincias($csv);

        $this->assertEquals(3, $resultado['importados']);
        $this->assertDatabaseHas('provincias', ['nombre' => 'Córdoba']);
        $this->assertDatabaseHas('provincias', ['nombre' => 'São Paulo']);
        $this->assertDatabaseHas('provincias', ['nombre' => 'Ñuñoa']);
    }

    public function test_importar_con_bom(): void
    {
        // Crear archivo con BOM UTF-8
        $bom = chr(0xEF) . chr(0xBB) . chr(0xBF);
        $content = $bom . "nombre,codigo\nBuenos Aires,BA";
        $csv = $this->createTempCsv('provincias.csv', $content);

        $resultado = $this->service->importarProvincias($csv);

        $this->assertEquals(1, $resultado['importados']);
        $this->assertDatabaseHas('provincias', ['nombre' => 'Buenos Aires']);
    }

    // ===========================================
    // Tests de múltiples errores
    // ===========================================

    public function test_multiples_errores_reportados(): void
    {
        $csv = $this->createTempCsv('provincias.csv', "nombre,codigo\n,BA\nCórdoba,\n,");

        $resultado = $this->service->importarProvincias($csv);

        $this->assertEquals(0, $resultado['importados']);
        $this->assertCount(3, $resultado['errores']);
    }

    // ===========================================
    // Tests de transacciones
    // ===========================================

    public function test_transaccion_se_aplica_correctamente(): void
    {
        $csv = $this->createTempCsv('provincias.csv', "nombre,codigo\nBuenos Aires,BA\nCórdoba,CBA");

        $resultado = $this->service->importarProvincias($csv);

        $this->assertEquals(2, Provincia::count());
    }
}
