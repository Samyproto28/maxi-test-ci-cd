<?php

use App\Http\Controllers\Api\CandidatoController;
use App\Http\Controllers\Api\ListaController;
use App\Http\Controllers\Api\MesaController;
use App\Http\Controllers\Api\ProvinciaController;
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
});
