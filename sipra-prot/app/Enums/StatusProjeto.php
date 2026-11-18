<?php

namespace App\Enums;

enum StatusProjeto: string
{
    case EmLevantamento = 'em_levantamento';
    case AguardandoOrcamento = 'aguardando_orcamento';
    case EmRevisao = 'em_revisao';
    case Aprovado = 'aprovado';

    public function rotulo(): string
    {
        return match ($this) {
            self::EmLevantamento => 'Em Levantamento',
            self::AguardandoOrcamento => 'Aguardando Orçamento',
            self::EmRevisao => 'Em Revisão',
            self::Aprovado => 'Aprovado',
        };
    }

    public function classes(): string
    {
        return match ($this) {
            self::EmLevantamento => 'bg-sky-100 text-sky-800',
            self::AguardandoOrcamento => 'bg-amber-100 text-amber-800',
            self::EmRevisao => 'bg-violet-100 text-violet-800',
            self::Aprovado => 'bg-sipra-verde/10 text-sipra-verde-escuro',
        };
    }
}