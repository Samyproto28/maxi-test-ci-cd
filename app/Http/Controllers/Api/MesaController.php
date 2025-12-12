<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMesaRequest;
use App\Http\Requests\UpdateMesaRequest;
use App\Models\Mesa;
use App\Models\Provincia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MesaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $mesas = Mesa::with('provincia')
            ->withCount('telegramas')
            ->when($request->provincia_id, fn($q) => $q->where('provincia_id', $request->provincia_id))
            ->when($request->circuito, fn($q) => $q->where('circuito', $request->circuito))
            ->when($request->id_mesa, fn($q) => $q->where('id_mesa', 'like', "%{$request->id_mesa}%"))
            ->orderBy('id_mesa')
            ->paginate($request->per_page ?? 15);

        return response()->json($mesas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMesaRequest $request): JsonResponse
    {
        $mesa = Mesa::create($request->validated());
        $mesa->load('provincia');

        return response()->json($mesa, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Mesa $mesa): JsonResponse
    {
        $mesa->load(['provincia', 'telegramas.votos.lista']);

        return response()->json($mesa);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMesaRequest $request, Mesa $mesa): JsonResponse
    {
        $mesa->update($request->validated());
        $mesa->load('provincia');

        return response()->json($mesa);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mesa $mesa): JsonResponse
    {
        // Verificar si la mesa tiene telegramas asociados
        if ($mesa->telegramas()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la mesa porque tiene telegramas asociados',
                'error' => 'Esta mesa tiene ' . $mesa->telegramas()->count() . ' telegrama(s) cargado(s). Debe eliminar primero los telegramas antes de eliminar la mesa.'
            ], 422);
        }

        $mesa->delete();

        return response()->json(null, 204);
    }

    /**
     * Display mesas filtered by provincia.
     */
    public function mesasByProvincia(Request $request, Provincia $provincia): JsonResponse
    {
        $mesas = Mesa::with('provincia')
            ->withCount('telegramas')
            ->where('provincia_id', $provincia->id)
            ->when($request->circuito, fn($q) => $q->where('circuito', $request->circuito))
            ->when($request->id_mesa, fn($q) => $q->where('id_mesa', 'like', "%{$request->id_mesa}%"))
            ->orderBy('id_mesa')
            ->paginate($request->per_page ?? 15);

        return response()->json($mesas);
    }
}
