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
    private const CACHE_TTL_MINUTES = 10;

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

        $cacheKey = $this->getCacheKeyProvincia($provinciaId, $cargo);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn() => $this->calcularResultadosPorProvincia($provinciaId, $cargo));
    }

    /**
     * Calculate electoral results for a province (internal method)
     *
     * @param int $provinciaId Province ID
     * @param string $cargo Cargo type
     * @return array Results
     */
    private function calcularResultadosPorProvincia(int $provinciaId, string $cargo): array
    {
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
     * Calculate national summary by aggregating all provinces
     *
     * @param string $cargo Cargo type (DIPUTADOS or SENADORES)
     * @return array National results with cargo, listas, total_votos_validos
     * @throws \InvalidArgumentException If cargo is invalid
     */
    public function resumenNacional(string $cargo): array
    {
        $this->validarCargo($cargo);

        $cacheKey = $this->getCacheKeyNacional($cargo);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn() => $this->calcularResumenNacional($cargo));
    }

    /**
     * Calculate national summary (internal method)
     *
     * @param string $cargo Cargo type
     * @return array National results
     */
    private function calcularResumenNacional(string $cargo): array
    {
        $votoColumn = $this->obtenerColumnaVotos($cargo);

        $resultados = DB::table('telegramas')
            ->join('listas', 'telegramas.lista_id', '=', 'listas.id')
            ->where('listas.cargo', $cargo)
            ->select(
                'listas.nombre as lista_nombre',
                'listas.alianza as lista_alianza',
                DB::raw("SUM({$votoColumn}) as total_votos")
            )
            ->groupBy('listas.nombre', 'listas.alianza')
            ->orderByDesc('total_votos')
            ->get();

        $totalVotosValidos = $resultados->sum('total_votos');

        $listas = $resultados->map(function ($item) use ($totalVotosValidos) {
            return [
                'nombre' => $item->lista_nombre,
                'alianza' => $item->lista_alianza,
                'votos' => (int) $item->total_votos,
                'porcentaje' => $this->calcularPorcentaje($item->total_votos, $totalVotosValidos)
            ];
        })->values()->toArray();

        return [
            'cargo' => $cargo,
            'listas' => $listas,
            'total_votos_validos' => $totalVotosValidos
        ];
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

    /**
     * Get electoral results for a specific candidate
     *
     * @param int $candidatoId Candidate ID
     * @return array Candidate results with lista votes
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If candidate not found
     */
    public function resultadosPorCandidato(int $candidatoId): array
    {
        $candidato = \App\Models\Candidato::with(['lista', 'provincia'])
            ->findOrFail($candidatoId);

        $votosLista = $this->obtenerVotosPorLista($candidato->lista_id, $candidato->cargo);

        return [
            'candidato_id' => $candidato->id,
            'candidato_nombre' => $candidato->nombre,
            'lista_id' => $candidato->lista_id,
            'lista_nombre' => $candidato->lista->nombre,
            'cargo' => $candidato->cargo,
            'votos_lista' => $votosLista,
            'provincia_id' => $candidato->provincia_id
        ];
    }

    /**
     * Get electoral results for a specific lista
     *
     * @param int $listaId Lista ID
     * @return array Lista results with total votes and candidatos
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If lista not found
     */
    public function resultadosPorLista(int $listaId): array
    {
        $lista = \App\Models\Lista::with(['candidatos' => function ($query) {
            $query->orderBy('orden');
        }, 'provincia'])
            ->findOrFail($listaId);

        $totalVotos = $this->obtenerVotosPorLista($listaId, $lista->cargo);

        $candidatos = $lista->candidatos->map(function ($candidato) {
            return [
                'id' => $candidato->id,
                'nombre' => $candidato->nombre,
                'orden' => $candidato->orden
            ];
        })->toArray();

        return [
            'lista_id' => $lista->id,
            'lista_nombre' => $lista->nombre,
            'lista_alianza' => $lista->alianza,
            'cargo' => $lista->cargo,
            'provincia_id' => $lista->provincia_id,
            'total_votos' => $totalVotos,
            'candidatos' => $candidatos
        ];
    }

    /**
     * Get total votes for a specific lista and cargo
     *
     * @param int $listaId Lista ID
     * @param string $cargo Cargo type
     * @return int Total votes
     */
    private function obtenerVotosPorLista(int $listaId, string $cargo): int
    {
        $votoColumn = $this->obtenerColumnaVotos($cargo);

        return (int) DB::table('telegramas')
            ->where('lista_id', $listaId)
            ->sum($votoColumn);
    }

    /**
     * Get cache key for national results
     *
     * @param string $cargo Cargo type
     * @return string Cache key
     */
    private function getCacheKeyNacional(string $cargo): string
    {
        return "resumen_nacional_{$cargo}";
    }

    /**
     * Get cache key for province results
     *
     * @param int $provinciaId Province ID
     * @param string $cargo Cargo type
     * @return string Cache key
     */
    private function getCacheKeyProvincia(int $provinciaId, string $cargo): string
    {
        return "resultados_provincia_{$provinciaId}_{$cargo}";
    }

    /**
     * Invalidate all result caches
     *
     * @return void
     */
    public function invalidarCaches(): void
    {
        foreach (['DIPUTADOS', 'SENADORES'] as $cargo) {
            Cache::forget($this->getCacheKeyNacional($cargo));
        }

        // For provincia caches, they will expire naturally after TTL
        // or could be invalidated individually if needed
    }
}
