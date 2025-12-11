<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ImportService;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportExportController extends Controller
{
    public function __construct(
        private ImportService $importService,
        private ExportService $exportService
    ) {}

    /**
     * Importa provincias desde archivo CSV
     */
    public function importarProvincias(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240' // 10MB max
        ]);

        $path = $request->file('file')->store('imports');
        $fullPath = storage_path("app/{$path}");

        try {
            $resultado = $this->importService->importarProvincias($fullPath);

            // Limpiar archivo temporal
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'importados' => $resultado['importados'],
                'errores' => $resultado['errores'],
            ], empty($resultado['errores']) ? 200 : 207);
        } catch (\Exception $e) {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Importa listas desde archivo CSV
     */
    public function importarListas(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        $path = $request->file('file')->store('imports');
        $fullPath = storage_path("app/{$path}");

        try {
            $resultado = $this->importService->importarListas($fullPath);

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'importados' => $resultado['importados'],
                'errores' => $resultado['errores'],
            ], empty($resultado['errores']) ? 200 : 207);
        } catch (\Exception $e) {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Importa candidatos desde archivo CSV
     */
    public function importarCandidatos(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        $path = $request->file('file')->store('imports');
        $fullPath = storage_path("app/{$path}");

        try {
            $resultado = $this->importService->importarCandidatos($fullPath);

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'importados' => $resultado['importados'],
                'errores' => $resultado['errores'],
            ], empty($resultado['errores']) ? 200 : 207);
        } catch (\Exception $e) {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Importa mesas desde archivo CSV
     */
    public function importarMesas(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        $path = $request->file('file')->store('imports');
        $fullPath = storage_path("app/{$path}");

        try {
            $resultado = $this->importService->importarMesas($fullPath);

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'importados' => $resultado['importados'],
                'errores' => $resultado['errores'],
            ], empty($resultado['errores']) ? 200 : 207);
        } catch (\Exception $e) {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Importa telegramas desde archivo CSV con validación compleja
     */
    public function importarTelegramas(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240'
        ]);

        $path = $request->file('file')->store('imports');
        $fullPath = storage_path("app/{$path}");

        try {
            // El usuario autenticado se pasa a ImportService para registrar quién importó
            $usuario = $request->user()?->name ?? $request->user()?->email ?? null;
            $resultado = $this->importService->importarTelegramas($fullPath, $usuario);

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            return response()->json([
                'success' => true,
                'importados' => $resultado['importados'],
                'errores' => $resultado['errores'],
            ], empty($resultado['errores']) ? 200 : 207);
        } catch (\Exception $e) {
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Exportar resultados provinciales a CSV
     */
    public function exportarResultadosProvinciales(int $provinciaId, Request $request): BinaryFileResponse
    {
        $request->validate([
            'cargo' => 'required|in:DIPUTADOS,SENADORES'
        ]);

        $resultado = $this->exportService->exportarResultadosProvinciales(
            $provinciaId,
            $request->cargo
        );

        return response()->download(
            $resultado['path'],
            "resultados_provincia_{$provinciaId}_{$request->cargo}_" . now()->format('Y-m-d_His') . ".csv",
            ['Content-Type' => 'text/csv; charset=UTF-8']
        )->deleteFileAfterSend();
    }

    /**
     * Exportar resumen nacional a CSV
     */
    public function exportarResumenNacional(Request $request): BinaryFileResponse
    {
        $request->validate([
            'cargo' => 'required|in:DIPUTADOS,SENADORES'
        ]);

        $resultado = $this->exportService->exportarResumenNacional($request->cargo);

        return response()->download(
            $resultado['path'],
            "resumen_nacional_{$request->cargo}_" . now()->format('Y-m-d_His') . ".csv",
            ['Content-Type' => 'text/csv; charset=UTF-8']
        )->deleteFileAfterSend();
    }
}
