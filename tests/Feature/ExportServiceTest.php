<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\ExportService;
use App\Services\ResultadoCalculationService;
use App\Models\Provincia;
use App\Models\Lista;
use App\Models\Mesa;
use App\Models\Telegrama;
use League\Csv\Reader;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExportService $exportService;
    private ResultadoCalculationService $calculationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculationService = app(ResultadoCalculationService::class);
        $this->exportService = new ExportService($this->calculationService);

        // Clean up test exports directory
        $this->cleanupTestExports();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestExports();
        parent::tearDown();
    }

    private function cleanupTestExports(): void
    {
        $exportPath = storage_path('app/exports');
        if (is_dir($exportPath)) {
            $files = glob($exportPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    private function seedTestData(): array
    {
        // Create provincia
        $provincia = Provincia::create([
            'nombre' => 'Buenos Aires',
            'codigo' => 'BA'
        ]);

        // Create listas
        $lista1 = Lista::create([
            'nombre' => 'Lista A',
            'alianza' => 'Alianza 1',
            'cargo' => 'DIPUTADOS',
            'provincia_id' => $provincia->id
        ]);

        $lista2 = Lista::create([
            'nombre' => 'Lista B',
            'alianza' => 'Alianza 2',
            'cargo' => 'DIPUTADOS',
            'provincia_id' => $provincia->id
        ]);

        // Create mesa
        $mesa = Mesa::create([
            'id_mesa' => '001',
            'circuito' => 'A',
            'establecimiento' => 'Escuela 1',
            'provincia_id' => $provincia->id,
            'electores' => 300
        ]);

        // Create telegramas
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista1->id,
            'votos_diputados' => 150,
            'votos_senadores' => 140,
            'usuario' => 'test_user'
        ]);

        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista2->id,
            'votos_diputados' => 100,
            'votos_senadores' => 110,
            'usuario' => 'test_user'
        ]);

        return [
            'provincia' => $provincia,
            'lista1' => $lista1,
            'lista2' => $lista2,
            'mesa' => $mesa
        ];
    }

    public function test_exportarResultadosProvinciales_generates_csv_with_correct_structure()
    {
        // Arrange
        $data = $this->seedTestData();

        // Act
        $resultado = $this->exportService->exportarResultadosProvinciales(
            $data['provincia']->id,
            'DIPUTADOS'
        );

        // Assert
        $this->assertArrayHasKey('path', $resultado);
        $this->assertArrayHasKey('filename', $resultado);
        $this->assertFileExists($resultado['path']);

        // Verify CSV content
        $csv = Reader::createFromPath($resultado['path'], 'r');
        $csv->setHeaderOffset(0);
        $records = iterator_to_array($csv->getRecords());

        // Check we have 2 data rows (lista1 and lista2)
        $this->assertCount(2, $records);

        // Verify first row
        $this->assertEquals('Lista A', $records[0]['Lista']);
        $this->assertEquals('Alianza 1', $records[0]['Alianza']);
        $this->assertEquals('150', $records[0]['Votos']);
        $this->assertStringContainsString('%', $records[0]['Porcentaje']);
    }

    public function test_exportarResultadosProvinciales_includes_totals_row()
    {
        // Arrange
        $data = $this->seedTestData();

        // Act
        $resultado = $this->exportService->exportarResultadosProvinciales(
            $data['provincia']->id,
            'DIPUTADOS'
        );

        // Assert - read all lines including totals
        $csv = Reader::createFromPath($resultado['path'], 'r');
        $allRows = iterator_to_array($csv);

        // Headers + 2 data rows + empty row + totals row = 5 rows
        $this->assertCount(5, $allRows);

        // Check totals row (last row)
        $totalsRow = end($allRows);
        $this->assertEquals('TOTAL', $totalsRow[0]);
        $this->assertEquals('250', $totalsRow[2]); // 150 + 100
        $this->assertEquals('100%', $totalsRow[3]);
    }

    public function test_exportarResultadosProvinciales_uses_utf8_bom_encoding()
    {
        // Arrange
        $provincia = Provincia::create(['nombre' => 'Córdoba', 'codigo' => 'CB']);
        $lista = Lista::create([
            'nombre' => 'Año Nuevo',
            'alianza' => 'Niño',
            'cargo' => 'SENADORES',
            'provincia_id' => $provincia->id
        ]);
        $mesa = Mesa::create([
            'id_mesa' => '001',
            'circuito' => 'A',
            'establecimiento' => 'Escuela',
            'provincia_id' => $provincia->id,
            'electores' => 200
        ]);
        Telegrama::create([
            'mesa_id' => $mesa->id,
            'lista_id' => $lista->id,
            'votos_diputados' => 50,
            'votos_senadores' => 100,
            'usuario' => 'test'
        ]);

        // Act
        $resultado = $this->exportService->exportarResultadosProvinciales($provincia->id, 'SENADORES');

        // Assert - check BOM
        $content = file_get_contents($resultado['path']);
        $bom = substr($content, 0, 3);
        // BOM can be represented as UTF-8 character or raw bytes
        $this->assertTrue(
            $bom === "\xEF\xBB\xBF" || $bom === "\u{FEFF}",
            'File should start with UTF-8 BOM'
        );

        // Verify special characters are preserved
        $this->assertStringContainsString('Año', $content);
        $this->assertStringContainsString('Niño', $content);
    }

    public function test_exportarResultadosProvinciales_generates_unique_filenames()
    {
        // Arrange
        $data = $this->seedTestData();

        // Act
        $resultado1 = $this->exportService->exportarResultadosProvinciales(
            $data['provincia']->id,
            'DIPUTADOS'
        );
        sleep(1); // Ensure different timestamp
        $resultado2 = $this->exportService->exportarResultadosProvinciales(
            $data['provincia']->id,
            'DIPUTADOS'
        );

        // Assert
        $this->assertNotEquals($resultado1['filename'], $resultado2['filename']);
        $this->assertStringStartsWith('resultados_provincial_', $resultado1['filename']);
        $this->assertStringEndsWith('.csv', $resultado1['filename']);
        $this->assertFileExists($resultado1['path']);
        $this->assertFileExists($resultado2['path']);
    }

    public function test_exportarResumenNacional_generates_csv_successfully()
    {
        // Arrange
        $data = $this->seedTestData();

        // Act
        $resultado = $this->exportService->exportarResumenNacional('DIPUTADOS');

        // Assert
        $this->assertArrayHasKey('path', $resultado);
        $this->assertArrayHasKey('filename', $resultado);
        $this->assertFileExists($resultado['path']);
        $this->assertStringStartsWith('resumen_nacional_', $resultado['filename']);

        // Verify CSV has content
        $csv = Reader::createFromPath($resultado['path'], 'r');
        $allRows = iterator_to_array($csv);
        $this->assertGreaterThan(1, count($allRows)); // At least headers + some data
    }

    public function test_limpiarArchivosAntiguos_handles_empty_directory()
    {
        // Arrange - ensure directory is empty
        $this->cleanupTestExports();

        // Act
        $resultado = $this->exportService->limpiarArchivosAntiguos(7);

        // Assert
        $this->assertEquals(0, $resultado['archivos_eliminados']);
        $this->assertEquals(0, $resultado['espacio_liberado']);
    }

    public function test_cleanup_command_executes_successfully()
    {
        // Act
        $this->artisan('exports:cleanup --dias=7')
            ->expectsOutput('Limpiando archivos CSV con más de 7 días de antigüedad...')
            ->assertExitCode(0);
    }
}
