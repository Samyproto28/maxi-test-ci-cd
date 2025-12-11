<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProvinciaRequest;
use App\Http\Requests\UpdateProvinciaRequest;
use App\Models\Provincia;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProvinciaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $provincias = Provincia::query()
            ->when($request->has('search'), fn($q) =>
                $q->where('nombre', 'like', "%{$request->search}%")
                  ->orWhere('codigo', 'like', "%{$request->search}%")
            )
            ->orderBy('nombre')
            ->paginate($request->per_page ?? 15);

        return response()->json($provincias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProvinciaRequest $request): JsonResponse
    {
        $provincia = Provincia::create($request->validated());
        return response()->json($provincia, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Provincia $provincia): JsonResponse
    {
        return response()->json($provincia);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProvinciaRequest $request, Provincia $provincia): JsonResponse
    {
        $provincia->update($request->validated());
        return response()->json($provincia);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Provincia $provincia): JsonResponse
    {
        try {
            $provincia->delete();
            return response()->json(null, 204);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                return response()->json([
                    'message' => 'No se puede eliminar la provincia porque tiene registros relacionados',
                    'error' => 'foreign_key_constraint'
                ], 400);
            }
            throw $e;
        }
    }
}
