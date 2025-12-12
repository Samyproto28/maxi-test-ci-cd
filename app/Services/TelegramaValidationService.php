<?php

namespace App\Services;

use App\Models\Telegrama;
use App\Models\TelegramaVoto;
use App\Models\Mesa;
use Illuminate\Support\Facades\DB;

class TelegramaValidationService
{
    /**
     * Valida que la suma total de votos de una mesa no exceda sus electores
     * Nueva estructura: votos es un array de objetos con lista_id, votos_diputados, votos_senadores
     *
     * @param int $mesaId ID de la mesa electoral
     * @param array $datosNuevoTelegrama Datos del telegrama a validar
     * @param int|null $telegramaIdExcluir ID del telegrama a excluir (para actualizaciones)
     * @return bool True si la validacion pasa
     * @throws \InvalidArgumentException Si la suma de votos excede la cantidad de electores
     */
    public function validarSumaVotosNoExcedeElectores(
        int $mesaId,
        array $datosNuevoTelegrama,
        ?int $telegramaIdExcluir = null
    ): bool {
        $mesa = Mesa::findOrFail($mesaId);

        // Sumar votos de telegramas existentes (excluyendo el que se esta actualizando)
        $votosExistentes = 0;
        if ($telegramaIdExcluir) {
            // Si estamos actualizando, no contar los votos del telegrama actual
            $telegramasExistentes = Telegrama::where('mesa_id', $mesaId)
                ->where('id', '!=', $telegramaIdExcluir)
                ->get();
            
            foreach ($telegramasExistentes as $telegrama) {
                $votosExistentes += $telegrama->totalVotos();
            }
        }

        // Sumar votos del nuevo telegrama (nueva estructura con array de votos)
        $votosNuevo = 0;
        
        // Sumar votos por lista
        if (isset($datosNuevoTelegrama['votos']) && is_array($datosNuevoTelegrama['votos'])) {
            foreach ($datosNuevoTelegrama['votos'] as $voto) {
                $votosNuevo += ($voto['votos_diputados'] ?? 0);
                $votosNuevo += ($voto['votos_senadores'] ?? 0);
            }
        }
        
        // Sumar blancos, nulos, recurridos
        $votosNuevo += ($datosNuevoTelegrama['blancos'] ?? 0);
        $votosNuevo += ($datosNuevoTelegrama['nulos'] ?? 0);
        $votosNuevo += ($datosNuevoTelegrama['recurridos'] ?? 0);

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
     * @return bool True si todos los valores son validos
     * @throws \InvalidArgumentException Si algun campo tiene valores negativos
     */
    public function validarVotosNoNegativos(array $datos): bool
    {
        // Validar blancos, nulos, recurridos
        $camposBase = ['blancos', 'nulos', 'recurridos'];
        foreach ($camposBase as $campo) {
            if (isset($datos[$campo]) && $datos[$campo] < 0) {
                throw new \InvalidArgumentException(
                    "El campo {$campo} no puede ser negativo"
                );
            }
        }

        // Validar votos por lista
        if (isset($datos['votos']) && is_array($datos['votos'])) {
            foreach ($datos['votos'] as $index => $voto) {
                if (isset($voto['votos_diputados']) && $voto['votos_diputados'] < 0) {
                    throw new \InvalidArgumentException(
                        "Los votos de diputados de la lista {$index} no pueden ser negativos"
                    );
                }
                if (isset($voto['votos_senadores']) && $voto['votos_senadores'] < 0) {
                    throw new \InvalidArgumentException(
                        "Los votos de senadores de la lista {$index} no pueden ser negativos"
                    );
                }
            }
        }

        return true;
    }
}
