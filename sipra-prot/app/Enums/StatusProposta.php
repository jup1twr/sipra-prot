<?php

namespace App\Enums;

enum StatusProposta: string
{
    case Rascunho = 'rascunho';
    case Enviada = 'enviada';
    case Aprovada = 'aprovada';
    case Recusada = 'recusada';

    public function rotulo(): string
    {
        return match ($this) {
            self::Rascunho => 'Rascunho',
            self::Enviada => 'Enviada',
            self::Aprovada => 'Aprovada',
            self::Recusada => 'Recusada',
        };
    }

    public function classes(): string
    {
        return match ($this) {
            self::Rascunho => 'bg-zinc-100 text-zinc-700',
            self::Enviada => 'bg-sky-100 text-sky-800',
            self::Aprovada => 'bg-sipra-verde/10 text-sipra-verde-escuro',
            self::Recusada => 'bg-red-100 text-red-700',
        };
    }
}