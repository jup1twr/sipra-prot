<?php

namespace App\Enums;

enum TipoMedida: string
{
    case Area = 'area';
    case Volume = 'volume';
    case Linear = 'linear';
    case Unidade = 'unidade';

    public function rotulo(): string
    {
        return match ($this) {
            self::Area => 'Área',
            self::Volume => 'Volume',
            self::Linear => 'Linear',
            self::Unidade => 'Unidade',
        };
    }

    public function unidade(): string
    {
        return match ($this) {
            self::Area => 'm²',
            self::Volume => 'm³',
            self::Linear => 'm',
            self::Unidade => 'un',
        };
    }
}