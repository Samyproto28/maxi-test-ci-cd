<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCandidatoRequest;
use App\Http\Requests\UpdateCandidatoRequest;
use App\Models\Candidato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidatoController extends Controller
{
    /**
     * Display a listing of the resource with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $candidatos = Candidato::with(['lista', 'provincia'])
            ->when($request->lista_id, fn($q) => $q->where('lista_id', $request->lista_id))
            ->when($request->provincia_id, fn($q) => $q->where('provincia_id', $request->provincia_id))
            ->when($request->cargo, fn($q) => $q->where('cargo', $request->cargo))
            ->orderBy('lista_id')
            ->orderBy('orden')
            ->paginate($request->per_page ?? 15);

        return response()->json($candidatos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCandidatoRequest $request): JsonResponse
    {
        $candidato = Candidato::create($request->validated());
        $candidato->load(['lista', 'provincia']);

        return response()->json($candidato, 201);
    }

    /**
     * Display the specified resource with nested relationships.
     */
    public function show(Candidato $candidato): JsonResponse
    {
        $candidato->load(['lista.provincia']);

        return response()->json($candidato);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCandidatoRequest $request, Candidato $candidato): JsonResponse
    {
        $candidato->update($request->validated());
        $candidato->load(['lista', 'provincia']);

        return response()->json($candidato);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Candidato $candidato): JsonResponse
    {
        $candidato->delete();

        return response()->json(null, 204);
    }

    /**
     * Reorder multiple candidatos within a lista in bulk.
     */
    public function reordenar(Request $request): JsonResponse
    {
        $request->validate([
            'candidatos' => 'required|array|min:1',
            'candidatos.*.id' => 'required|exists:candidatos,id',
            'candidatos.*.orden' => 'required|integer|min:1',
        ]);

        // Get all candidatos to verify they belong to the same lista
        $candidatoIds = collect($request->candidatos)->pluck('id');
        $candidatos = Candidato::whereIn('id', $candidatoIds)->get();

        // Verify all candidatos belong to the same lista
        $listaIds = $candidatos->pluck('lista_id')->unique();
        if ($listaIds->count() > 1) {
            return response()->json([
                'message' => 'Todos los candidatos deben pertenecer a la misma lista',
                'error' => 'invalid_lista'
            ], 422);
        }

        // Verify all candidatos exist
        if ($candidatos->count() !== count($request->candidatos)) {
            return response()->json([
                'message' => 'Uno o más candidatos no fueron encontrados',
                'error' => 'candidatos_not_found'
            ], 422);
        }

        // Verify no duplicate orden values
        $ordenes = collect($request->candidatos)->pluck('orden');
        if ($ordenes->count() !== $ordenes->unique()->count()) {
            return response()->json([
                'message' => 'Los valores de orden no pueden estar duplicados',
                'error' => 'duplicate_orden'
            ], 422);
        }

        // Execute reordering in transaction to ensure atomicity
        DB::transaction(function () use ($request) {
            foreach ($request->candidatos as $candidatoData) {
                Candidato::where('id', $candidatoData['id'])
                    ->update(['orden' => $candidatoData['orden']]);
            }
        });

        // Return updated candidatos
        $updatedCandidatos = Candidato::with(['lista', 'provincia'])
            ->whereIn('id', $candidatoIds)
            ->orderBy('orden')
            ->get();

        return response()->json([
            'message' => 'Candidatos reordenados exitosamente',
            'candidatos' => $updatedCandidatos
        ]);
    }
}
