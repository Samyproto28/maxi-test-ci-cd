<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ExportService;

class CleanupExportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:cleanup {--dias=7 : Number of days to retain files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old CSV export files from storage';

    /**
     * Execute the console command.
     */
    public function handle(ExportService $exportService): int
    {
        $dias = (int) $this->option('dias');

        $this->info("Limpiando archivos CSV con más de {$dias} días de antigüedad...");

        $resultado = $exportService->limpiarArchivosAntiguos($dias);

        $espacioMB = round($resultado['espacio_liberado'] / 1024 / 1024, 2);

        $this->info("Archivos eliminados: {$resultado['archivos_eliminados']}");
        $this->info("Espacio liberado: {$espacioMB} MB");

        return Command::SUCCESS;
    }
}
