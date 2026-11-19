<?php

namespace App\Enums;

enum Tipologia: string
{
    case Residencial = 'residencial';
    case Comercial = 'comercial';
    case Condominio = 'condominio';
    case Mobiliario = 'mobiliario';
    case Institucional = 'institucional';

    public function rotulo(): string
    {
        return match ($this) {
            self::Residencial => 'Residencial',
            self::Comercial => 'Comercial',
            self::Condominio => 'Condomínio / predial',
            self::Mobiliario => 'Mobiliário / marcenaria',
            self::Institucional => 'Industrial / institucional',
        };
    }

    /** Lista no formato [valor => rótulo], para os campos de seleção */
    public static function opcoes(): array
    {
        $lista = [];
        foreach (self::cases() as $caso) {
            $lista[$caso->value] = $caso->rotulo();
        }

        return $lista;
    }
}