<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreTelegramaRequest, UpdateTelegramaRequest};
use App\Models\{Telegrama, TelegramaVoto, Mesa};
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
     * Nueva estructura: recibe array de votos por lista
     */
    public function store(StoreTelegramaRequest $request): JsonResponse
    {
        try {
            $telegrama = DB::transaction(function () use ($request) {
                $validated = $request->validated();

                // Crear telegrama (sin votos por lista)
                $telegrama = Telegrama::create([
                    'mesa_id' => $validated['mesa_id'],
                    'blancos' => $validated['blancos'],
                    'nulos' => $validated['nulos'],
                    'recurridos' => $validated['recurridos'],
                    'usuario' => $validated['usuario'],
                ]);

                // Crear votos por lista en tabla telegrama_votos
                foreach ($validated['votos'] as $voto) {
                    TelegramaVoto::create([
                        'telegrama_id' => $telegrama->id,
                        'lista_id' => $voto['lista_id'],
                        'votos_diputados' => $voto['votos_diputados'],
                        'votos_senadores' => $voto['votos_senadores'],
                    ]);
                }

                // Log audit
                $this->registrarAuditoria(
                    'CREATE',
                    $telegrama->id,
                    null,
                    array_merge($telegrama->toArray(), ['votos' => $validated['votos']]),
                    $validated['usuario']
                );

                return $telegrama;
            });

            $telegrama->load(['mesa', 'votos.lista']);

            return response()->json([
                'data' => $telegrama
            ], 201);

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
        $telegramas = Telegrama::with(['mesa', 'votos.lista'])
            ->when($request->mesa_id, fn($q) => $q->where('mesa_id', $request->mesa_id))
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($telegramas);
    }

    /**
     * Display the specified telegrama.
     */
    public function show(Telegrama $telegrama): JsonResponse
    {
        $telegrama->load(['mesa', 'votos.lista']);
        return response()->json([
            'data' => $telegrama
        ]);
    }

    /**
     * Update the specified telegrama in storage.
     */
    public function update(UpdateTelegramaRequest $request, Telegrama $telegrama): JsonResponse
    {
        try {
            $updated = DB::transaction(function () use ($request, $telegrama) {
                $validated = $request->validated();
                $datosAnteriores = array_merge($telegrama->toArray(), ['votos' => $telegrama->votos->toArray()]);

                // Update telegrama base data
                $telegrama->update([
                    'blancos' => $validated['blancos'] ?? $telegrama->blancos,
                    'nulos' => $validated['nulos'] ?? $telegrama->nulos,
                    'recurridos' => $validated['recurridos'] ?? $telegrama->recurridos,
                    'usuario' => $validated['usuario'],
                ]);

                // Update votos por lista si se enviaron
                if (isset($validated['votos']) && is_array($validated['votos'])) {
                    // Eliminar votos existentes y crear nuevos
                    $telegrama->votos()->delete();
                    
                    foreach ($validated['votos'] as $voto) {
                        TelegramaVoto::create([
                            'telegrama_id' => $telegrama->id,
                            'lista_id' => $voto['lista_id'],
                            'votos_diputados' => $voto['votos_diputados'],
                            'votos_senadores' => $voto['votos_senadores'],
                        ]);
                    }
                }

                // Log audit
                $this->registrarAuditoria(
                    'UPDATE',
                    $telegrama->id,
                    $datosAnteriores,
                    array_merge($telegrama->fresh()->toArray(), ['votos' => $validated['votos'] ?? []]),
                    $validated['usuario']
                );

                return $telegrama;
            });

            $updated->load(['mesa', 'votos.lista']);

            return response()->json([
                'data' => $updated
            ]);

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
     * Get telegrama for a specific mesa (solo puede haber uno por mesa).
     */
    public function telegramasByMesa(Request $request, Mesa $mesa): JsonResponse
    {
        $telegrama = $mesa->telegramas()
            ->with('votos.lista')
            ->first();

        if (!$telegrama) {
            return response()->json([
                'data' => null,
                'message' => 'No hay telegrama cargado para esta mesa'
            ]);
        }

        return response()->json([
            'data' => $telegrama
        ]);
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
