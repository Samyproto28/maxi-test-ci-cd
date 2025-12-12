<?php

namespace App\Services;

use Illuminate\Support\Collection;

class DhondtCalculator
{
    /**
     * Calcula la distribución de bancas usando el método D'Hondt
     *
     * @param Collection|array $votosPorLista Array asociativo [lista_id => votos]
     * @param int $totalBancas Total de bancas a distribuir
     * @param float $umbralPorcentaje Umbral mínimo de votos (ej: 3.0 para 3%)
     * @return array Retorna [lista_id => bancas_obtenidas]
     */
    public function calcularBancas($votosPorLista, int $totalBancas, float $umbralPorcentaje = 0.0): array
    {
        // Convertir a Collection si es array
        if (is_array($votosPorLista)) {
            $votosPorLista = collect($votosPorLista);
        }

        $totalVotos = $votosPorLista->sum();

        // Filtrar listas que no alcanzan el umbral
        if ($umbralPorcentaje > 0) {
            $votosPorLista = $votosPorLista->filter(function ($votos) use ($totalVotos, $umbralPorcentaje) {
                $porcentaje = ($votos / $totalVotos) * 100;
                return $porcentaje >= $umbralPorcentaje;
            });
        }

        // Inicializar bancas
        $bancas = array_fill_keys($votosPorLista->keys()->toArray(), 0);

        // Aplicar algoritmo D'Hondt
        for ($i = 0; $i < $totalBancas; $i++) {
            $mejorLista = null;
            $mejorCociente = -1;

            foreach ($votosPorLista as $listaId => $votos) {
                $cociente = $votos / ($bancas[$listaId] + 1);

                if ($cociente > $mejorCociente) {
                    $mejorCociente = $cociente;
                    $mejorLista = $listaId;
                }
            }

            if ($mejorLista !== null) {
                $bancas[$mejorLista]++;
            }
        }

        return $bancas;
    }

    /**
     * Calcula bancas con información detallada de cada lista
     *
     * @param array $listas Array de listas con ['id', 'nombre', 'votos', 'porcentaje']
     * @param int $totalBancas
     * @param float $umbralPorcentaje
     * @return array
     */
    public function calcularBancasDetallado(array $listas, int $totalBancas, float $umbralPorcentaje = 0.0): array
    {
        // Filtrar por umbral
        if ($umbralPorcentaje > 0) {
            $listas = array_filter($listas, function ($lista) use ($umbralPorcentaje) {
                return $lista['porcentaje'] >= $umbralPorcentaje;
            });
        }

        // Crear array de votos
        $votosPorLista = [];
        foreach ($listas as $lista) {
            $votosPorLista[$lista['id']] = $lista['votos'];
        }

        // Calcular bancas
        $bancasAsignadas = $this->calcularBancas($votosPorLista, $totalBancas, 0);

        // Agregar información de bancas a cada lista
        foreach ($listas as &$lista) {
            $lista['bancas'] = $bancasAsignadas[$lista['id']] ?? 0;
        }

        return $listas;
    }

    /**
     * Obtiene el cociente D'Hondt para una lista específica
     *
     * @param int $votos Total de votos de la lista
     * @param int $bancasAsignadas Bancas ya asignadas a la lista
     * @return float
     */
    public function obtenerCociente(int $votos, int $bancasAsignadas): float
    {
        return $votos / ($bancasAsignadas + 1);
    }

    /**
     * Simula el proceso completo de asignación de bancas
     *
     * @param array $listas Array de listas con votos
     * @param int $totalBancas
     * @param float $umbralPorcentaje
     * @return array Retorna pasos del cálculo para análisis
     */
    public function simularAsignacion(array $listas, int $totalBancas, float $umbralPorcentaje = 0.0): array
    {
        $pasos = [];
        $votosPorLista = [];
        $bancas = [];

        foreach ($listas as $lista) {
            $votosPorLista[$lista['id']] = $lista['votos'];
            $bancas[$lista['id']] = 0;
        }

        for ($i = 0; $i < $totalBancas; $i++) {
            $cocientes = [];

            foreach ($votosPorLista as $listaId => $votos) {
                $cocientes[$listaId] = $this->obtenerCociente($votos, $bancas[$listaId]);
            }

            // Encontrar la lista con mayor cociente
            arsort($cocientes);
            $mejorLista = array_key_first($cocientes);

            $bancas[$mejorLista]++;

            $pasos[] = [
                'paso' => $i + 1,
                'lista_id' => $mejorLista,
                'votos' => $votosPorLista[$mejorLista],
                'bancas_asignadas' => $bancas[$mejorLista],
                'cociente' => $cocientes[$mejorLista]
            ];
        }

        return $pasos;
    }
}
