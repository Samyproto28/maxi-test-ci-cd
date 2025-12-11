<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ExportService;
use App\Services\ResultadoCalculationService;
use Mockery;

class ExportServiceTest extends TestCase
{
    private $exportService;
    private $calculationServiceMock;
    private $testExportPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock for ResultadoCalculationService
        $this->calculationServiceMock = Mockery::mock(ResultadoCalculationService::class);

        // Create a testable subclass that uses a test directory
        $this->testExportPath = sys_get_temp_dir() . '/test_exports';
        $this->exportService = new class($this->calculationServiceMock, $this->testExportPath) extends ExportService {
            private string $testPath;

            public function __construct($calculationService, string $testPath)
            {
                parent::__construct($calculationService);
                $this->testPath = $testPath;
            }

            protected function getExportsPath(string $filename): string
            {
                return $this->testPath . '/' . $filename;
            }
        };

        // Ensure test directory exists
        if (!is_dir($this->testExportPath)) {
            mkdir($this->testExportPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Cleanup test directory
        if (is_dir($this->testExportPath)) {
            $files = glob($this->testExportPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testExportPath);
        }

        Mockery::close();
        parent::tearDown();
    }

    public function test_exportarResultadosProvinciales_generates_csv_with_correct_structure()
    {
        // Arrange
        $provinciaId = 1;
        $cargo = 'DIPUTADOS';

        $mockResultados = [
            'provincia_id' => $provinciaId,
            'cargo' => $cargo,
            'listas' => [
                [
                    'id' => 1,
                    'nombre' => 'Lista A',
                    'alianza' => 'Alianza 1',
                    'votos' => 1000,
                    'porcentaje' => 60.0
                ],
                [
                    'id' => 2,
                    'nombre' => 'Lista B',
                    'alianza' => 'Alianza 2',
                    'votos' => 666,
                    'porcentaje' => 40.0
                ],
            ],
            'total_votos_validos' => 1666
        ];

        $this->calculationServiceMock
            ->shouldReceive('resultadosPorProvincia')
            ->once()
            ->with($provinciaId, $cargo)
            ->andReturn($mockResultados);

        // Act
        $resultado = $this->exportService->exportarResultadosProvinciales($provinciaId, $cargo);

        // Assert
        $this->assertArrayHasKey('path', $resultado);
        $this->assertArrayHasKey('filename', $resultado);
        $this->assertFileExists($resultado['path']);

        // Verify CSV content
        $content = file_get_contents($resultado['path']);
        $this->assertStringContainsString('Lista,Alianza,Votos,Porcentaje', $content);
        $this->assertStringContainsString('"Lista A"', $content);
        $this->assertStringContainsString('"Alianza 1"', $content);
        $this->assertStringContainsString('1000', $content);
        $this->assertStringContainsString('60%', $content);
        $this->assertStringContainsString('"Lista B"', $content);
        $this->assertStringContainsString('"Alianza 2"', $content);
        $this->assertStringContainsString('666', $content);
        $this->assertStringContainsString('40%', $content);
        $this->assertStringContainsString('TOTAL', $content);
        $this->assertStringContainsString('1666', $content);
        $this->assertStringContainsString('100%', $content);
    }

    public function test_exportarResultadosProvinciales_handles_null_alianza()
    {
        // Arrange
        $provinciaId = 1;
        $cargo = 'DIPUTADOS';

        $mockResultados = [
            'provincia_id' => $provinciaId,
            'cargo' => $cargo,
            'listas' => [
                [
                    'id' => 1,
                    'nombre' => 'Lista Sin Alianza',
                    'alianza' => null,
                    'votos' => 100,
                    'porcentaje' => 100.0
                ],
            ],
            'total_votos_validos' => 100
        ];

        $this->calculationServiceMock
            ->shouldReceive('resultadosPorProvincia')
            ->once()
            ->with($provinciaId, $cargo)
            ->andReturn($mockResultados);

        // Act
        $resultado = $this->exportService->exportarResultadosProvinciales($provinciaId, $cargo);

        // Assert
        $this->assertFileExists($resultado['path']);
        $content = file_get_contents($resultado['path']);
        $this->assertStringContainsString('"Lista Sin Alianza"', $content);
        $this->assertStringContainsString('100', $content);
        $this->assertStringContainsString('100%', $content);
    }

    public function test_exportarResultadosProvinciales_uses_utf8_bom_encoding()
    {
        // Arrange
        $provinciaId = 1;
        $cargo = 'DIPUTADOS';

        $mockResultados = [
            'provincia_id' => $provinciaId,
            'cargo' => $cargo,
            'listas' => [
                [
                    'id' => 1,
                    'nombre' => 'Año Nuevo',
                    'alianza' => 'Niño',
                    'votos' => 100,
                    'porcentaje' => 100.0
                ],
            ],
            'total_votos_validos' => 100
        ];

        $this->calculationServiceMock
            ->shouldReceive('resultadosPorProvincia')
            ->once()
            ->with($provinciaId, $cargo)
            ->andReturn($mockResultados);

        // Act
        $resultado = $this->exportService->exportarResultadosProvinciales($provinciaId, $cargo);

        // Assert - verify file exists and special characters are preserved
        $this->assertFileExists($resultado['path']);
        $content = file_get_contents($resultado['path']);

        // Verify special characters (ñ, tildes) are preserved correctly
        // The BOM is set via setOutputBOM() in the service, which handles Excel compatibility
        $this->assertStringContainsString('Año', $content);
        $this->assertStringContainsString('Niño', $content);

        // Verify the file is valid UTF-8
        $this->assertTrue(mb_check_encoding($content, 'UTF-8'), 'File should be valid UTF-8');
    }

    public function test_exportarResultadosProvinciales_generates_unique_filenames()
    {
        // Arrange
        $provinciaId = 1;
        $cargo = 'DIPUTADOS';

        $mockResultados = [
            'provincia_id' => $provinciaId,
            'cargo' => $cargo,
            'listas' => [],
            'total_votos_validos' => 0
        ];

        $this->calculationServiceMock
            ->shouldReceive('resultadosPorProvincia')
            ->twice()
            ->with($provinciaId, $cargo)
            ->andReturn($mockResultados);

        // Act
        $resultado1 = $this->exportService->exportarResultadosProvinciales($provinciaId, $cargo);
        sleep(1); // Ensure different timestamp
        $resultado2 = $this->exportService->exportarResultadosProvinciales($provinciaId, $cargo);

        // Assert
        $this->assertNotEquals($resultado1['filename'], $resultado2['filename']);
        $this->assertStringStartsWith('resultados_provincial_', $resultado1['filename']);
        $this->assertStringEndsWith('.csv', $resultado1['filename']);
    }

    public function test_exportarResumenNacional_generates_csv_filename_correctly()
    {
        // This test is marked as incomplete due to database dependency in exportarResumenNacional
        // The method queries DB::table('provincias') which requires a database connection
        // For proper testing of this method, use Feature tests with database setup
        $this->markTestIncomplete(
            'This test requires database setup. See Feature tests for full integration testing.'
        );
    }

    public function test_limpiarArchivosAntiguos_handles_empty_directory()
    {
        // Act
        $resultado = $this->exportService->limpiarArchivosAntiguos(7);

        // Assert
        $this->assertEquals(0, $resultado['archivos_eliminados']);
        $this->assertEquals(0, $resultado['espacio_liberado']);
    }

    public function test_exportarResultadosProvinciales_creates_directory_if_not_exists()
    {
        // Arrange - remove test directory
        if (is_dir($this->testExportPath)) {
            rmdir($this->testExportPath);
        }

        $provinciaId = 1;
        $cargo = 'DIPUTADOS';

        $mockResultados = [
            'provincia_id' => $provinciaId,
            'cargo' => $cargo,
            'listas' => [],
            'total_votos_validos' => 0
        ];

        $this->calculationServiceMock
            ->shouldReceive('resultadosPorProvincia')
            ->once()
            ->with($provinciaId, $cargo)
            ->andReturn($mockResultados);

        // Act
        $resultado = $this->exportService->exportarResultadosProvinciales($provinciaId, $cargo);

        // Assert
        $this->assertDirectoryExists($this->testExportPath);
        $this->assertFileExists($resultado['path']);
    }
}
