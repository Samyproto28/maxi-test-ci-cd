<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Service for calculating electoral results aggregations
 *
 * Provides methods to aggregate votes by province, nationally,
 * by candidate, and by lista from telegrama data.
 */
class ResultadoCalculationService
{
    /**
     * Calculate electoral results for a specific province and cargo
     *
     * @param int $provinciaId Province ID
     * @param string $cargo Cargo type (DIPUTADOS or SENADORES)
     * @return array Results with provincia_id, cargo, listas, total_votos_validos
     * @throws \InvalidArgumentException If cargo is invalid
     */
    public function resultadosPorProvincia(int $provinciaId, string $cargo): array
    {
        $this->validarCargo($cargo);
        $votoColumn = $this->obtenerColumnaVotos($cargo);

        $resultados = DB::table('telegramas')
            ->join('mesas', 'telegramas.mesa_id', '=', 'mesas.id')
            ->join('listas', 'telegramas.lista_id', '=', 'listas.id')
            ->where('mesas.provincia_id', $provinciaId)
            ->where('listas.cargo', $cargo)
            ->select(
                'listas.id as lista_id',
                'listas.nombre as lista_nombre',
                'listas.alianza as lista_alianza',
                DB::raw("SUM({$votoColumn}) as total_votos")
            )
            ->groupBy('listas.id', 'listas.nombre', 'listas.alianza')
            ->orderByDesc('total_votos')
            ->get();

        $totalVotosValidos = $resultados->sum('total_votos');

        $listas = $resultados->map(function ($item) use ($totalVotosValidos) {
            return [
                'id' => $item->lista_id,
                'nombre' => $item->lista_nombre,
                'alianza' => $item->lista_alianza,
                'votos' => (int) $item->total_votos,
                'porcentaje' => $this->calcularPorcentaje($item->total_votos, $totalVotosValidos)
            ];
        })->values()->toArray();

        return [
            'provincia_id' => $provinciaId,
            'cargo' => $cargo,
            'listas' => $listas,
            'total_votos_validos' => $totalVotosValidos
        ];
    }

    /**
     * Get the vote column name based on cargo type
     *
     * @param string $cargo Cargo type
     * @return string Column name
     */
    private function obtenerColumnaVotos(string $cargo): string
    {
        return $cargo === 'DIPUTADOS' ? 'votos_diputados' : 'votos_senadores';
    }

    /**
     * Calculate percentage with proper rounding
     *
     * @param int $votos Votes count
     * @param int $total Total votes
     * @return float Percentage rounded to 2 decimals
     */
    private function calcularPorcentaje(int $votos, int $total): float
    {
        return $total > 0 ? round(($votos / $total) * 100, 2) : 0.0;
    }

    /**
     * Validate cargo parameter
     *
     * @param string $cargo Cargo type
     * @throws \InvalidArgumentException If cargo is invalid
     */
    private function validarCargo(string $cargo): void
    {
        if (!in_array($cargo, ['DIPUTADOS', 'SENADORES'])) {
            throw new \InvalidArgumentException("Cargo inválido: {$cargo}");
        }
    }
}
