<?php

use App\Http\Controllers\Api\CandidatoController;
use App\Http\Controllers\Api\ImportExportController;
use App\Http\Controllers\Api\ListaController;
use App\Http\Controllers\Api\MesaController;
use App\Http\Controllers\Api\ProvinciaController;
use App\Http\Controllers\Api\ResultadoController;
use App\Http\Controllers\Api\TelegramaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::apiResource('provincias', ProvinciaController::class);
    Route::get('provincias/{provincia}/listas', [ListaController::class, 'listsByProvincia']);
    Route::get('provincias/{provincia}/mesas', [MesaController::class, 'mesasByProvincia']);
    Route::apiResource('listas', ListaController::class);

    Route::post('candidatos/reordenar', [CandidatoController::class, 'reordenar']);
    Route::apiResource('candidatos', CandidatoController::class);

    Route::apiResource('mesas', MesaController::class);

    Route::apiResource('telegramas', TelegramaController::class);
    Route::get('mesas/{mesa}/telegramas', [TelegramaController::class, 'telegramasByMesa']);

    Route::prefix('resultados')->group(function () {
        Route::get('provincial/{provincia}', [ResultadoController::class, 'provincial']);
        Route::get('nacional', [ResultadoController::class, 'nacional']);
        Route::get('candidato/{candidato}', [ResultadoController::class, 'porCandidato']);
        Route::get('lista/{lista}', [ResultadoController::class, 'porLista']);
    });

    Route::prefix('import')->group(function () {
        Route::post('provincias', [ImportExportController::class, 'importarProvincias']);
        Route::post('listas', [ImportExportController::class, 'importarListas']);
        Route::post('candidatos', [ImportExportController::class, 'importarCandidatos']);
        Route::post('mesas', [ImportExportController::class, 'importarMesas']);
        Route::post('telegramas', [ImportExportController::class, 'importarTelegramas']);
    });

    Route::prefix('export')->group(function () {
        Route::get('provincial/{provincia}', [ImportExportController::class, 'exportarResultadosProvinciales']);
        Route::get('nacional', [ImportExportController::class, 'exportarResumenNacional']);
    });
});
