<?php

namespace App\Services;

use App\Models\Telegrama;
use App\Models\Mesa;
use Illuminate\Support\Facades\DB;

class TelegramaValidationService
{
    /**
     * Valida que la suma total de votos de una mesa no exceda sus electores
     *
     * @param int $mesaId ID de la mesa electoral
     * @param array $datosNuevoTelegrama Datos del telegrama a validar
     * @param int|null $telegramaIdExcluir ID del telegrama a excluir (para actualizaciones)
     * @return bool True si la validación pasa
     * @throws \InvalidArgumentException Si la suma de votos excede la cantidad de electores
     */
    public function validarSumaVotosNoExcedeElectores(
        int $mesaId,
        array $datosNuevoTelegrama,
        ?int $telegramaIdExcluir = null
    ): bool {
        $mesa = Mesa::findOrFail($mesaId);

        // Sumar votos de telegramas existentes (excluyendo el que se está actualizando)
        $votosExistentes = Telegrama::where('mesa_id', $mesaId)
            ->when($telegramaIdExcluir, fn($q) => $q->where('id', '!=', $telegramaIdExcluir))
            ->sum(DB::raw('votos_diputados + votos_senadores + blancos + nulos + recurridos'));

        // Sumar votos del nuevo telegrama
        $votosNuevo = ($datosNuevoTelegrama['votos_diputados'] ?? 0) +
                      ($datosNuevoTelegrama['votos_senadores'] ?? 0) +
                      ($datosNuevoTelegrama['blancos'] ?? 0) +
                      ($datosNuevoTelegrama['nulos'] ?? 0) +
                      ($datosNuevoTelegrama['recurridos'] ?? 0);

        $totalVotos = $votosExistentes + $votosNuevo;

        if ($totalVotos > $mesa->electores) {
            throw new \InvalidArgumentException(
                "La suma de votos ({$totalVotos}) excede la cantidad de electores ({$mesa->electores}) de la mesa {$mesa->id_mesa}"
            );
        }

        return true;
    }

    /**
     * Valida que todos los valores de votos sean >= 0
     *
     * @param array $datos Datos de votos a validar
     * @return bool True si todos los valores son válidos
     * @throws \InvalidArgumentException Si algún campo tiene valores negativos
     */
    public function validarVotosNoNegativos(array $datos): bool
    {
        $campos = ['votos_diputados', 'votos_senadores', 'blancos', 'nulos', 'recurridos'];

        foreach ($campos as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] < 0) {
                throw new \InvalidArgumentException(
                    "El campo {$campo} no puede ser negativo"
                );
            }
        }

        return true;
    }
}
