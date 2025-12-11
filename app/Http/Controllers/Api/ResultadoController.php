<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ResultadoCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResultadoController extends Controller
{
    public function __construct(
        private ResultadoCalculationService $calculationService
    ) {}

    public function provincial(int $provinciaId, Request $request): JsonResponse
    {
        $request->validate(['cargo' => 'required|in:DIPUTADOS,SENADORES']);

        $resultados = $this->calculationService->resultadosPorProvincia(
            $provinciaId,
            $request->cargo
        );

        return response()->json($resultados);
    }

    public function nacional(Request $request): JsonResponse
    {
        $request->validate(['cargo' => 'required|in:DIPUTADOS,SENADORES']);

        $resultados = $this->calculationService->resumenNacional($request->cargo);

        return response()->json($resultados);
    }

    public function porCandidato(int $candidatoId): JsonResponse
    {
        $resultados = $this->calculationService->resultadosPorCandidato($candidatoId);

        return response()->json($resultados);
    }

    public function porLista(int $listaId): JsonResponse
    {
        $resultados = $this->calculationService->resultadosPorLista($listaId);

        return response()->json($resultados);
    }
}
