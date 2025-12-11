<?php

namespace App\Services;

use League\Csv\Reader;
use App\Models\{Provincia, Lista, Candidato, Mesa, Telegrama};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportService
{
    private const CHUNK_SIZE = 500;
    private const CHUNK_THRESHOLD = 1000;

    private const HEADERS_PROVINCIAS = ['nombre', 'codigo'];
    private const HEADERS_LISTAS = ['nombre', 'alianza', 'provincia_id', 'cargo'];
    private const HEADERS_CANDIDATOS = ['nombre', 'lista_id', 'provincia_id', 'cargo', 'orden'];
    private const HEADERS_MESAS = ['id_mesa', 'provincia_id', 'circuito', 'establecimiento', 'electores'];
    private const HEADERS_TELEGRAMAS = ['mesa_id', 'lista_id', 'votos_diputados', 'votos_senadores', 'blancos', 'nulos', 'recurridos'];

    public function __construct(
        private TelegramaValidationService $telegramaValidationService
    ) {}

    /**
     * Importa provincias desde CSV
     * @return array ['importados' => int, 'errores' => array]
     */
    public function importarProvincias(string $filePath): array
    {
        $startTime = microtime(true);
        Log::info("Iniciando importación de provincias desde: {$filePath}");

        $csv = $this->prepareReader($filePath);
        $this->validarEstructuraCSV($csv, self::HEADERS_PROVINCIAS);

        $records = iterator_to_array($csv->getRecords());
        $totalLines = count($records);

        if ($totalLines > self::CHUNK_THRESHOLD) {
            return $this->importarProvinciasEnChunks($records, $startTime);
        }

        return $this->importarProvinciasSimple($records, $startTime);
    }

    private function importarProvinciasSimple(array $records, float $startTime): array
    {
        $importados = 0;
        $errores = [];

        DB::beginTransaction();

        try {
            foreach ($records as $index => $record) {
                $lineNumber = $index + 2; // +2 porque index empieza en 0 y hay header
                try {
                    $this->validarCamposRequeridos($record, ['nombre', 'codigo'], $lineNumber);

                    Provincia::create([
                        'nombre' => trim($record['nombre']),
                        'codigo' => trim($record['codigo']),
                    ]);
                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico en importación de provincias: {$e->getMessage()}");
            throw $e;
        }

        $this->logImportacion('provincias', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    private function importarProvinciasEnChunks(array $records, float $startTime): array
    {
        $importados = 0;
        $errores = [];
        $chunks = array_chunk($records, self::CHUNK_SIZE, true);

        Log::info("Procesando " . count($chunks) . " chunks de provincias");

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();

            try {
                foreach ($chunk as $index => $record) {
                    $lineNumber = $index + 2;
                    try {
                        $this->validarCamposRequeridos($record, ['nombre', 'codigo'], $lineNumber);

                        Provincia::create([
                            'nombre' => trim($record['nombre']),
                            'codigo' => trim($record['codigo']),
                        ]);
                        $importados++;
                    } catch (\Exception $e) {
                        $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                    }
                }

                DB::commit();
                Log::info("Chunk " . ($chunkIndex + 1) . " de provincias procesado. Memoria: " . $this->formatBytes(memory_get_usage()));
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error en chunk " . ($chunkIndex + 1) . " de provincias: {$e->getMessage()}");
                throw $e;
            }

            unset($chunk);
            gc_collect_cycles();
        }

        $this->logImportacion('provincias', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    /**
     * Importa listas desde CSV
     * @return array ['importados' => int, 'errores' => array]
     */
    public function importarListas(string $filePath): array
    {
        $startTime = microtime(true);
        Log::info("Iniciando importación de listas desde: {$filePath}");

        $csv = $this->prepareReader($filePath);
        $this->validarEstructuraCSV($csv, self::HEADERS_LISTAS);

        $records = iterator_to_array($csv->getRecords());
        $totalLines = count($records);

        if ($totalLines > self::CHUNK_THRESHOLD) {
            return $this->importarListasEnChunks($records, $startTime);
        }

        return $this->importarListasSimple($records, $startTime);
    }

    private function importarListasSimple(array $records, float $startTime): array
    {
        $importados = 0;
        $errores = [];

        DB::beginTransaction();

        try {
            foreach ($records as $index => $record) {
                $lineNumber = $index + 2;
                try {
                    $this->validarCamposRequeridos($record, ['nombre', 'provincia_id', 'cargo'], $lineNumber);
                    $this->validarRelacionExiste(Provincia::class, $record['provincia_id'], 'provincia_id', $lineNumber);
                    $this->validarCargoValido($record['cargo'], $lineNumber);

                    Lista::create([
                        'nombre' => trim($record['nombre']),
                        'alianza' => isset($record['alianza']) ? trim($record['alianza']) : null,
                        'provincia_id' => (int) $record['provincia_id'],
                        'cargo' => strtoupper(trim($record['cargo'])),
                    ]);
                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico en importación de listas: {$e->getMessage()}");
            throw $e;
        }

        $this->logImportacion('listas', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    private function importarListasEnChunks(array $records, float $startTime): array
    {
        $importados = 0;
        $errores = [];
        $chunks = array_chunk($records, self::CHUNK_SIZE, true);

        Log::info("Procesando " . count($chunks) . " chunks de listas");

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();

            try {
                foreach ($chunk as $index => $record) {
                    $lineNumber = $index + 2;
                    try {
                        $this->validarCamposRequeridos($record, ['nombre', 'provincia_id', 'cargo'], $lineNumber);
                        $this->validarRelacionExiste(Provincia::class, $record['provincia_id'], 'provincia_id', $lineNumber);
                        $this->validarCargoValido($record['cargo'], $lineNumber);

                        Lista::create([
                            'nombre' => trim($record['nombre']),
                            'alianza' => isset($record['alianza']) ? trim($record['alianza']) : null,
                            'provincia_id' => (int) $record['provincia_id'],
                            'cargo' => strtoupper(trim($record['cargo'])),
                        ]);
                        $importados++;
                    } catch (\Exception $e) {
                        $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                    }
                }

                DB::commit();
                Log::info("Chunk " . ($chunkIndex + 1) . " de listas procesado. Memoria: " . $this->formatBytes(memory_get_usage()));
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error en chunk " . ($chunkIndex + 1) . " de listas: {$e->getMessage()}");
                throw $e;
            }

            unset($chunk);
            gc_collect_cycles();
        }

        $this->logImportacion('listas', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    /**
     * Importa candidatos desde CSV
     * @return array ['importados' => int, 'errores' => array]
     */
    public function importarCandidatos(string $filePath): array
    {
        $startTime = microtime(true);
        Log::info("Iniciando importación de candidatos desde: {$filePath}");

        $csv = $this->prepareReader($filePath);
        $this->validarEstructuraCSV($csv, self::HEADERS_CANDIDATOS);

        $records = iterator_to_array($csv->getRecords());
        $totalLines = count($records);

        if ($totalLines > self::CHUNK_THRESHOLD) {
            return $this->importarCandidatosEnChunks($records, $startTime);
        }

        return $this->importarCandidatosSimple($records, $startTime);
    }

    private function importarCandidatosSimple(array $records, float $startTime): array
    {
        $importados = 0;
        $errores = [];

        DB::beginTransaction();

        try {
            foreach ($records as $index => $record) {
                $lineNumber = $index + 2;
                try {
                    $this->validarCamposRequeridos($record, ['nombre', 'lista_id', 'provincia_id', 'cargo', 'orden'], $lineNumber);
                    $this->validarRelacionExiste(Lista::class, $record['lista_id'], 'lista_id', $lineNumber);
                    $this->validarRelacionExiste(Provincia::class, $record['provincia_id'], 'provincia_id', $lineNumber);
                    $this->validarCargoValido($record['cargo'], $lineNumber);
                    $this->validarEnteroPositivo($record['orden'], 'orden', $lineNumber);

                    Candidato::create([
                        'nombre' => trim($record['nombre']),
                        'lista_id' => (int) $record['lista_id'],
                        'provincia_id' => (int) $record['provincia_id'],
                        'cargo' => strtoupper(trim($record['cargo'])),
                        'orden' => (int) $record['orden'],
                        'observaciones' => isset($record['observaciones']) ? trim($record['observaciones']) : null,
                    ]);
                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico en importación de candidatos: {$e->getMessage()}");
            throw $e;
        }

        $this->logImportacion('candidatos', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    private function importarCandidatosEnChunks(array $records, float $startTime): array
    {
        $importados = 0;
        $errores = [];
        $chunks = array_chunk($records, self::CHUNK_SIZE, true);

        Log::info("Procesando " . count($chunks) . " chunks de candidatos");

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();

            try {
                foreach ($chunk as $index => $record) {
                    $lineNumber = $index + 2;
                    try {
                        $this->validarCamposRequeridos($record, ['nombre', 'lista_id', 'provincia_id', 'cargo', 'orden'], $lineNumber);
                        $this->validarRelacionExiste(Lista::class, $record['lista_id'], 'lista_id', $lineNumber);
                        $this->validarRelacionExiste(Provincia::class, $record['provincia_id'], 'provincia_id', $lineNumber);
                        $this->validarCargoValido($record['cargo'], $lineNumber);
                        $this->validarEnteroPositivo($record['orden'], 'orden', $lineNumber);

                        Candidato::create([
                            'nombre' => trim($record['nombre']),
                            'lista_id' => (int) $record['lista_id'],
                            'provincia_id' => (int) $record['provincia_id'],
                            'cargo' => strtoupper(trim($record['cargo'])),
                            'orden' => (int) $record['orden'],
                            'observaciones' => isset($record['observaciones']) ? trim($record['observaciones']) : null,
                        ]);
                        $importados++;
                    } catch (\Exception $e) {
                        $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                    }
                }

                DB::commit();
                Log::info("Chunk " . ($chunkIndex + 1) . " de candidatos procesado. Memoria: " . $this->formatBytes(memory_get_usage()));
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error en chunk " . ($chunkIndex + 1) . " de candidatos: {$e->getMessage()}");
                throw $e;
            }

            unset($chunk);
            gc_collect_cycles();
        }

        $this->logImportacion('candidatos', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    /**
     * Importa mesas desde CSV
     * @return array ['importados' => int, 'errores' => array]
     */
    public function importarMesas(string $filePath): array
    {
        $startTime = microtime(true);
        Log::info("Iniciando importación de mesas desde: {$filePath}");

        $csv = $this->prepareReader($filePath);
        $this->validarEstructuraCSV($csv, self::HEADERS_MESAS);

        $records = iterator_to_array($csv->getRecords());
        $totalLines = count($records);

        if ($totalLines > self::CHUNK_THRESHOLD) {
            return $this->importarMesasEnChunks($records, $startTime);
        }

        return $this->importarMesasSimple($records, $startTime);
    }

    private function importarMesasSimple(array $records, float $startTime): array
    {
        $importados = 0;
        $errores = [];

        DB::beginTransaction();

        try {
            foreach ($records as $index => $record) {
                $lineNumber = $index + 2;
                try {
                    $this->validarCamposRequeridos($record, ['id_mesa', 'provincia_id', 'electores'], $lineNumber);
                    $this->validarRelacionExiste(Provincia::class, $record['provincia_id'], 'provincia_id', $lineNumber);
                    $this->validarEnteroPositivo($record['electores'], 'electores', $lineNumber);

                    Mesa::create([
                        'id_mesa' => trim($record['id_mesa']),
                        'provincia_id' => (int) $record['provincia_id'],
                        'circuito' => isset($record['circuito']) ? trim($record['circuito']) : null,
                        'establecimiento' => isset($record['establecimiento']) ? trim($record['establecimiento']) : null,
                        'electores' => (int) $record['electores'],
                    ]);
                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico en importación de mesas: {$e->getMessage()}");
            throw $e;
        }

        $this->logImportacion('mesas', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    private function importarMesasEnChunks(array $records, float $startTime): array
    {
        $importados = 0;
        $errores = [];
        $chunks = array_chunk($records, self::CHUNK_SIZE, true);

        Log::info("Procesando " . count($chunks) . " chunks de mesas");

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();

            try {
                foreach ($chunk as $index => $record) {
                    $lineNumber = $index + 2;
                    try {
                        $this->validarCamposRequeridos($record, ['id_mesa', 'provincia_id', 'electores'], $lineNumber);
                        $this->validarRelacionExiste(Provincia::class, $record['provincia_id'], 'provincia_id', $lineNumber);
                        $this->validarEnteroPositivo($record['electores'], 'electores', $lineNumber);

                        Mesa::create([
                            'id_mesa' => trim($record['id_mesa']),
                            'provincia_id' => (int) $record['provincia_id'],
                            'circuito' => isset($record['circuito']) ? trim($record['circuito']) : null,
                            'establecimiento' => isset($record['establecimiento']) ? trim($record['establecimiento']) : null,
                            'electores' => (int) $record['electores'],
                        ]);
                        $importados++;
                    } catch (\Exception $e) {
                        $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                    }
                }

                DB::commit();
                Log::info("Chunk " . ($chunkIndex + 1) . " de mesas procesado. Memoria: " . $this->formatBytes(memory_get_usage()));
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error en chunk " . ($chunkIndex + 1) . " de mesas: {$e->getMessage()}");
                throw $e;
            }

            unset($chunk);
            gc_collect_cycles();
        }

        $this->logImportacion('mesas', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    /**
     * Importa telegramas desde CSV con validación compleja
     * @return array ['importados' => int, 'errores' => array]
     */
    public function importarTelegramas(string $filePath, ?string $usuario = null): array
    {
        $startTime = microtime(true);
        Log::info("Iniciando importación de telegramas desde: {$filePath}");

        $csv = $this->prepareReader($filePath);
        $this->validarEstructuraCSV($csv, self::HEADERS_TELEGRAMAS);

        $records = iterator_to_array($csv->getRecords());
        $totalLines = count($records);

        if ($totalLines > self::CHUNK_THRESHOLD) {
            return $this->importarTelegramasEnChunks($records, $usuario, $startTime);
        }

        return $this->importarTelegramasSimple($records, $usuario, $startTime);
    }

    private function importarTelegramasSimple(array $records, ?string $usuario, float $startTime): array
    {
        $importados = 0;
        $errores = [];

        DB::beginTransaction();

        try {
            foreach ($records as $index => $record) {
                $lineNumber = $index + 2;
                try {
                    $this->validarCamposRequeridos($record, ['mesa_id', 'lista_id'], $lineNumber);
                    $this->validarRelacionExiste(Mesa::class, $record['mesa_id'], 'mesa_id', $lineNumber);
                    $this->validarRelacionExiste(Lista::class, $record['lista_id'], 'lista_id', $lineNumber);

                    // Validar duplicados
                    $existente = Telegrama::where('mesa_id', $record['mesa_id'])
                        ->where('lista_id', $record['lista_id'])
                        ->exists();

                    if ($existente) {
                        throw new \InvalidArgumentException("Ya existe un telegrama para la mesa {$record['mesa_id']} y lista {$record['lista_id']}");
                    }

                    $datosVotos = [
                        'votos_diputados' => (int) ($record['votos_diputados'] ?? 0),
                        'votos_senadores' => (int) ($record['votos_senadores'] ?? 0),
                        'blancos' => (int) ($record['blancos'] ?? 0),
                        'nulos' => (int) ($record['nulos'] ?? 0),
                        'recurridos' => (int) ($record['recurridos'] ?? 0),
                    ];

                    // Validar votos no negativos
                    $this->telegramaValidationService->validarVotosNoNegativos($datosVotos);

                    // Validar suma de votos no excede electores
                    $this->telegramaValidationService->validarSumaVotosNoExcedeElectores(
                        (int) $record['mesa_id'],
                        $datosVotos
                    );

                    Telegrama::create([
                        'mesa_id' => (int) $record['mesa_id'],
                        'lista_id' => (int) $record['lista_id'],
                        'votos_diputados' => $datosVotos['votos_diputados'],
                        'votos_senadores' => $datosVotos['votos_senadores'],
                        'blancos' => $datosVotos['blancos'],
                        'nulos' => $datosVotos['nulos'],
                        'recurridos' => $datosVotos['recurridos'],
                        'usuario' => $usuario,
                    ]);
                    $importados++;
                } catch (\Exception $e) {
                    $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error crítico en importación de telegramas: {$e->getMessage()}");
            throw $e;
        }

        $this->logImportacion('telegramas', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    private function importarTelegramasEnChunks(array $records, ?string $usuario, float $startTime): array
    {
        $importados = 0;
        $errores = [];
        $chunks = array_chunk($records, self::CHUNK_SIZE, true);

        Log::info("Procesando " . count($chunks) . " chunks de telegramas");

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();

            try {
                foreach ($chunk as $index => $record) {
                    $lineNumber = $index + 2;
                    try {
                        $this->validarCamposRequeridos($record, ['mesa_id', 'lista_id'], $lineNumber);
                        $this->validarRelacionExiste(Mesa::class, $record['mesa_id'], 'mesa_id', $lineNumber);
                        $this->validarRelacionExiste(Lista::class, $record['lista_id'], 'lista_id', $lineNumber);

                        // Validar duplicados
                        $existente = Telegrama::where('mesa_id', $record['mesa_id'])
                            ->where('lista_id', $record['lista_id'])
                            ->exists();

                        if ($existente) {
                            throw new \InvalidArgumentException("Ya existe un telegrama para la mesa {$record['mesa_id']} y lista {$record['lista_id']}");
                        }

                        $datosVotos = [
                            'votos_diputados' => (int) ($record['votos_diputados'] ?? 0),
                            'votos_senadores' => (int) ($record['votos_senadores'] ?? 0),
                            'blancos' => (int) ($record['blancos'] ?? 0),
                            'nulos' => (int) ($record['nulos'] ?? 0),
                            'recurridos' => (int) ($record['recurridos'] ?? 0),
                        ];

                        // Validar votos no negativos
                        $this->telegramaValidationService->validarVotosNoNegativos($datosVotos);

                        // Validar suma de votos no excede electores
                        $this->telegramaValidationService->validarSumaVotosNoExcedeElectores(
                            (int) $record['mesa_id'],
                            $datosVotos
                        );

                        Telegrama::create([
                            'mesa_id' => (int) $record['mesa_id'],
                            'lista_id' => (int) $record['lista_id'],
                            'votos_diputados' => $datosVotos['votos_diputados'],
                            'votos_senadores' => $datosVotos['votos_senadores'],
                            'blancos' => $datosVotos['blancos'],
                            'nulos' => $datosVotos['nulos'],
                            'recurridos' => $datosVotos['recurridos'],
                            'usuario' => $usuario,
                        ]);
                        $importados++;
                    } catch (\Exception $e) {
                        $errores[] = "Línea {$lineNumber}: {$e->getMessage()}";
                    }
                }

                DB::commit();
                Log::info("Chunk " . ($chunkIndex + 1) . " de telegramas procesado. Memoria: " . $this->formatBytes(memory_get_usage()));
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Error en chunk " . ($chunkIndex + 1) . " de telegramas: {$e->getMessage()}");
                throw $e;
            }

            unset($chunk);
            gc_collect_cycles();
        }

        $this->logImportacion('telegramas', $importados, $errores, microtime(true) - $startTime);

        return ['importados' => $importados, 'errores' => $errores];
    }

    /**
     * Prepara el reader de CSV con encoding UTF-8
     */
    private function prepareReader(string $filePath): Reader
    {
        // If it's a storage path, read from storage
        if (str_starts_with($filePath, storage_path('app'))) {
            $storagePath = str_replace(storage_path('app') . '/', '', $filePath);
            if (!Storage::exists($storagePath)) {
                throw new \InvalidArgumentException("El archivo no existe: {$filePath}");
            }
            $content = Storage::get($storagePath);
        } else {
            // Direct filesystem access
            if (!file_exists($filePath)) {
                throw new \InvalidArgumentException("El archivo no existe: {$filePath}");
            }
            $content = file_get_contents($filePath);
        }

        // Remover BOM si existe
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^{$bom}/", '', $content);

        // Detectar y convertir encoding
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // Crear reader desde string
        $csv = Reader::createFromString($content);
        $csv->setHeaderOffset(0);

        return $csv;
    }

    /**
     * Valida que el CSV tenga los headers esperados
     */
    private function validarEstructuraCSV(Reader $csv, array $headersEsperados): void
    {
        $headers = $csv->getHeader();

        if (empty($headers)) {
            throw new \InvalidArgumentException("El archivo CSV no tiene headers");
        }

        // Normalizar headers (trim y lowercase)
        $headersNormalizados = array_map(fn($h) => strtolower(trim($h)), $headers);

        $faltantes = [];
        foreach ($headersEsperados as $header) {
            if (!in_array(strtolower($header), $headersNormalizados)) {
                $faltantes[] = $header;
            }
        }

        if (!empty($faltantes)) {
            throw new \InvalidArgumentException(
                "Headers faltantes en el CSV: " . implode(', ', $faltantes)
            );
        }
    }

    /**
     * Valida que los campos requeridos estén presentes y no vacíos
     */
    private function validarCamposRequeridos(array $record, array $campos, int $lineNumber): void
    {
        foreach ($campos as $campo) {
            if (!isset($record[$campo]) || trim($record[$campo]) === '') {
                throw new \InvalidArgumentException("El campo '{$campo}' es requerido");
            }
        }
    }

    /**
     * Valida que una relación exista en la base de datos
     */
    private function validarRelacionExiste(string $model, $id, string $fieldName, int $lineNumber): void
    {
        if (!$model::find($id)) {
            throw new \InvalidArgumentException("No existe registro con ID {$id} para '{$fieldName}'");
        }
    }

    /**
     * Valida que el cargo sea válido
     */
    private function validarCargoValido(string $cargo, int $lineNumber): void
    {
        $cargoNormalizado = strtoupper(trim($cargo));
        if (!in_array($cargoNormalizado, Lista::CARGOS)) {
            throw new \InvalidArgumentException(
                "Cargo inválido: '{$cargo}'. Valores permitidos: " . implode(', ', Lista::CARGOS)
            );
        }
    }

    /**
     * Valida que un valor sea un entero positivo
     */
    private function validarEnteroPositivo($valor, string $campo, int $lineNumber): void
    {
        if (!is_numeric($valor) || (int) $valor < 0) {
            throw new \InvalidArgumentException("El campo '{$campo}' debe ser un entero positivo");
        }
    }

    /**
     * Registra la importación en el log
     */
    private function logImportacion(string $tipo, int $importados, array $errores, float $tiempo): void
    {
        $memoria = $this->formatBytes(memory_get_usage());

        Log::info("Importación de {$tipo} completada", [
            'importados' => $importados,
            'errores' => count($errores),
            'tiempo_segundos' => round($tiempo, 2),
            'memoria' => $memoria,
        ]);

        if (!empty($errores)) {
            Log::warning("Errores en importación de {$tipo}", [
                'errores' => array_slice($errores, 0, 50), // Limitar a 50 errores en log
            ]);
        }
    }

    /**
     * Formatea bytes a formato legible
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
