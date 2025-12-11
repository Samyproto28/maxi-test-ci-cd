<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreTelegramaRequest, UpdateTelegramaRequest};
use App\Models\{Telegrama, Mesa};
use App\Services\TelegramaValidationService;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;

class TelegramaController extends Controller
{
    public function __construct(
        private TelegramaValidationService $validationService
    ) {}

    /**
     * Store a newly created telegrama in storage.
     */
    public function store(StoreTelegramaRequest $request): JsonResponse
    {
        try {
            $telegrama = DB::transaction(function () use ($request) {
                $validated = $request->validated();

                // Create telegrama
                $telegrama = Telegrama::create($validated);

                // Log audit
                $this->registrarAuditoria(
                    'CREATE',
                    $telegrama->id,
                    null,
                    $telegrama->toArray(),
                    $validated['usuario']
                );

                return $telegrama;
            });

            $telegrama->load(['mesa', 'lista']);

            return response()->json($telegrama, 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear el telegrama',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display a listing of telegramas.
     */
    public function index(Request $request): JsonResponse
    {
        $telegramas = Telegrama::with(['mesa', 'lista'])
            ->when($request->mesa_id, fn($q) => $q->where('mesa_id', $request->mesa_id))
            ->when($request->lista_id, fn($q) => $q->where('lista_id', $request->lista_id))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($telegramas);
    }

    /**
     * Display the specified telegrama.
     */
    public function show(Telegrama $telegrama): JsonResponse
    {
        $telegrama->load(['mesa', 'lista']);
        return response()->json($telegrama);
    }

    /**
     * Update the specified telegrama in storage.
     */
    public function update(UpdateTelegramaRequest $request, Telegrama $telegrama): JsonResponse
    {
        try {
            $updated = DB::transaction(function () use ($request, $telegrama) {
                $validated = $request->validated();
                $datosAnteriores = $telegrama->toArray();

                // Update telegrama
                $telegrama->update($validated);

                // Log audit
                $this->registrarAuditoria(
                    'UPDATE',
                    $telegrama->id,
                    $datosAnteriores,
                    $telegrama->fresh()->toArray(),
                    $validated['usuario']
                );

                return $telegrama;
            });

            $updated->load(['mesa', 'lista']);

            return response()->json($updated);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar el telegrama',
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified telegrama from storage.
     */
    public function destroy(Request $request, Telegrama $telegrama): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $telegrama) {
                $datosAnteriores = $telegrama->toArray();
                $usuario = $request->input('usuario', 'system');

                // Log audit before deletion
                $this->registrarAuditoria(
                    'DELETE',
                    $telegrama->id,
                    $datosAnteriores,
                    null,
                    $usuario
                );

                $telegrama->delete();
            });

            return response()->json(null, 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar el telegrama',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all telegramas for a specific mesa.
     */
    public function telegramasByMesa(Request $request, Mesa $mesa): JsonResponse
    {
        $telegramas = $mesa->telegramas()
            ->with('lista')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($telegramas);
    }

    /**
     * Register an audit entry for telegrama operations.
     */
    private function registrarAuditoria(
        string $accion,
        int $registroId,
        ?array $datosAnteriores,
        ?array $datosNuevos,
        string $usuario
    ): void {
        DB::table('auditoria')->insert([
            'tabla' => 'telegramas',
            'registro_id' => $registroId,
            'accion' => $accion,
            'datos_anteriores' => $datosAnteriores ? json_encode($datosAnteriores) : null,
            'datos_nuevos' => $datosNuevos ? json_encode($datosNuevos) : null,
            'usuario' => $usuario,
            'created_at' => now(),
        ]);
    }
}
