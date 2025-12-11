<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListaRequest;
use App\Http\Requests\UpdateListaRequest;
use App\Models\Lista;
use App\Models\Provincia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $listas = Lista::with('provincia')
            ->when($request->provincia_id, fn($q) => $q->where('provincia_id', $request->provincia_id))
            ->when($request->cargo, fn($q) => $q->where('cargo', $request->cargo))
            ->orderBy('nombre')
            ->paginate($request->per_page ?? 15);

        return response()->json($listas);
    }

    public function store(StoreListaRequest $request): JsonResponse
    {
        try {
            $lista = Lista::create($request->validated());
            $lista->load('provincia');

            return response()->json($lista, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la lista',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Lista $lista): JsonResponse
    {
        $lista->load(['provincia', 'candidatos']);
        return response()->json($lista);
    }

    public function update(UpdateListaRequest $request, Lista $lista): JsonResponse
    {
        try {
            $lista->update($request->validated());
            $lista->load('provincia');

            return response()->json($lista);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la lista',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id)
    {
        //
    }

    public function listsByProvincia(Provincia $provincia, Request $request): JsonResponse
    {
        $listas = $provincia->listas()
            ->with('candidatos')
            ->when($request->cargo, fn($q) => $q->where('cargo', $request->cargo))
            ->orderBy('nombre')
            ->paginate($request->per_page ?? 15);

        return response()->json($listas);
    }
}
