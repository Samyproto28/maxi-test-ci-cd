<?php

namespace App\Services;

use League\Csv\Writer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Service for exporting electoral results to CSV format
 *
 * Provides methods to export provincial and national results
 * to CSV files with proper UTF-8 encoding and Excel compatibility.
 */
class ExportService
{
    /**
     * @param ResultadoCalculationService $calculationService
     */
    public function __construct(
        private ResultadoCalculationService $calculationService
    ) {
    }

    /**
     * Export provincial results to CSV file
     *
     * Generates a CSV file with provincial electoral results including
     * headers, lista data, and totals with UTF-8 BOM for Excel compatibility.
     *
     * @param int $provinciaId Province ID
     * @param string $cargo Cargo type (DIPUTADOS or SENADORES)
     * @return array Array with 'path' and 'filename' keys
     * @throws \Exception If directory creation fails
     */
    public function exportarResultadosProvinciales(int $provinciaId, string $cargo): array
    {
        // Get results from calculation service
        $resultados = $this->calculationService->resultadosPorProvincia($provinciaId, $cargo);

        // Generate unique filename with timestamp
        $filename = 'resultados_provincial_' . date('YmdHis') . '.csv';
        $fullPath = $this->getExportsPath($filename);

        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($fullPath));

        // Create CSV writer
        $csv = Writer::createFromPath($fullPath, 'w+');
        $csv->setDelimiter(',');
        $csv->setEnclosure('"');

        // Add UTF-8 BOM for Excel compatibility
        $csv->setOutputBOM(Writer::BOM_UTF8);

        // Insert headers
        $csv->insertOne(['Lista', 'Alianza', 'Votos', 'Porcentaje']);

        // Insert data rows
        foreach ($resultados['listas'] as $lista) {
            $csv->insertOne([
                $lista['nombre'],
                $lista['alianza'] ?? '',
                $lista['votos'],
                $lista['porcentaje'] . '%',
            ]);
        }

        // Insert empty row and totals
        $csv->insertOne([]);
        $csv->insertOne(['TOTAL', '', $resultados['total_votos_validos'], '100%']);

        return [
            'path' => $fullPath,
            'filename' => $filename
        ];
    }

    /**
     * Export national summary to CSV file
     *
     * Generates a CSV file with aggregated results from all 24 provinces
     * showing results by province, lista, and national totals.
     *
     * @param string $cargo Cargo type (DIPUTADOS or SENADORES)
     * @return array Array with 'path' and 'filename' keys
     * @throws \Exception If directory creation fails
     */
    public function exportarResumenNacional(string $cargo): array
    {
        // Generate unique filename with timestamp
        $filename = 'resumen_nacional_' . date('YmdHis') . '.csv';
        $fullPath = $this->getExportsPath($filename);

        // Ensure directory exists
        $this->ensureDirectoryExists(dirname($fullPath));

        // Create CSV writer
        $csv = Writer::createFromPath($fullPath, 'w+');
        $csv->setDelimiter(',');
        $csv->setEnclosure('"');

        // Add UTF-8 BOM for Excel compatibility
        $csv->setOutputBOM(Writer::BOM_UTF8);

        // Insert headers
        $csv->insertOne(['Provincia', 'Lista', 'Alianza', 'Votos', 'Porcentaje Nacional']);

        // Get all provinces
        $provincias = DB::table('provincias')->orderBy('nombre')->get();

        // Get national totals for percentage calculation
        $resultadosNacionales = $this->calculationService->resumenNacional($cargo);
        $totalVotosNacionales = $resultadosNacionales['total_votos_validos'];

        // Build a map of lista totals for national percentage calculation
        $listaTotalesNacionales = [];
        foreach ($resultadosNacionales['listas'] as $lista) {
            $listaTotalesNacionales[$lista['nombre']] = $lista['votos'];
        }

        // Process each province
        foreach ($provincias as $provincia) {
            $resultadosProvincia = $this->calculationService->resultadosPorProvincia($provincia->id, $cargo);

            // If province has no results, show with 0 votes
            if (empty($resultadosProvincia['listas'])) {
                $csv->insertOne([
                    $provincia->nombre,
                    'Sin telegramas',
                    '',
                    0,
                    '0.00%'
                ]);
                continue;
            }

            // Insert a row for each lista in this province
            foreach ($resultadosProvincia['listas'] as $lista) {
                $votosNacionalesLista = $listaTotalesNacionales[$lista['nombre']] ?? 0;
                $porcentajeNacional = $totalVotosNacionales > 0
                    ? round(($votosNacionalesLista / $totalVotosNacionales) * 100, 2)
                    : 0.0;

                $csv->insertOne([
                    $provincia->nombre,
                    $lista['nombre'],
                    $lista['alianza'] ?? '',
                    $lista['votos'],
                    $porcentajeNacional . '%',
                ]);
            }
        }

        // Insert empty row and national totals
        $csv->insertOne([]);
        $csv->insertOne(['TOTAL NACIONAL', '', '', $totalVotosNacionales, '100%']);

        return [
            'path' => $fullPath,
            'filename' => $filename
        ];
    }

    /**
     * Clean up old CSV files from exports directory
     *
     * Deletes CSV files older than the specified retention period.
     *
     * @param int $diasRetencion Number of days to retain files (default: 7)
     * @return array Array with 'archivos_eliminados' and 'espacio_liberado' keys
     */
    public function limpiarArchivosAntiguos(int $diasRetencion = 7): array
    {
        $archivosEliminados = 0;
        $espacioLiberado = 0;

        // Get all files in exports directory
        $archivos = Storage::files('exports');

        // Current timestamp
        $now = now()->timestamp;
        $retentionSeconds = $diasRetencion * 24 * 60 * 60;

        foreach ($archivos as $archivo) {
            // Only process CSV files
            if (pathinfo($archivo, PATHINFO_EXTENSION) !== 'csv') {
                continue;
            }

            // Check file age
            $lastModified = Storage::lastModified($archivo);
            $edad = $now - $lastModified;

            if ($edad > $retentionSeconds) {
                $size = Storage::size($archivo);
                Storage::delete($archivo);
                $archivosEliminados++;
                $espacioLiberado += $size;
            }
        }

        return [
            'archivos_eliminados' => $archivosEliminados,
            'espacio_liberado' => $espacioLiberado
        ];
    }

    /**
     * Get the full path for exports directory
     *
     * @param string $filename Filename to append to path
     * @return string Full path to export file
     */
    protected function getExportsPath(string $filename): string
    {
        return storage_path('app/exports/' . $filename);
    }

    /**
     * Ensure a directory exists
     *
     * @param string $directory Directory path
     * @return void
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
