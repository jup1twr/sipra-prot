<?php

namespace App\Services;

use App\Enums\TipoMedida;
use App\Models\ItemLevantamento;

class CalculadoraQuantitativo
{
    /** Acima disso, a medida é considerada absurda (em metros ou unidades). */
    public const LIMITE_DIMENSAO = 500;
    public const LIMITE_QUANTIDADE = 100000;

    public function total(ItemLevantamento $item): float
    {
        $c = (float) $item->comprimento;
        $l = (float) $item->largura;
        $a = (float) $item->altura;
        $q = (float) $item->quantidade;

        return round(match ($item->insumo->tipo_medida) {
            TipoMedida::Area => $c * $l,
            TipoMedida::Volume => $c * $l * $a,
            TipoMedida::Linear => $c,
            TipoMedida::Unidade => $q,
        }, 3);
    }

    /** Devolve o motivo do problema, ou null se a medida está certa. */
    public function problema(ItemLevantamento $item): ?string
    {
        $campos = match ($item->insumo->tipo_medida) {
            TipoMedida::Area => ['comprimento' => $item->comprimento, 'largura' => $item->largura],
            TipoMedida::Volume => ['comprimento' => $item->comprimento, 'largura' => $item->largura, 'altura' => $item->altura],
            TipoMedida::Linear => ['comprimento' => $item->comprimento],
            TipoMedida::Unidade => ['quantidade' => $item->quantidade],
        };

        foreach ($campos as $nome => $valor) {
            if ($valor === null || $valor === '' || (float) $valor <= 0) {
                return 'Medida faltando ou zerada';
            }

            $limite = $nome === 'quantidade' ? self::LIMITE_QUANTIDADE : self::LIMITE_DIMENSAO;
            if ((float) $valor > $limite) {
                return 'Medida absurda: confira o valor';
            }
        }

        return null;
    }

    public function inconsistente(ItemLevantamento $item): bool
    {
        return $this->problema($item) !== null;
    }
}