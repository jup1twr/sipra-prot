<?php

namespace App\Enums;

enum Papel: string
{
    case Gestor = 'gestor';
    case Engenheiro = 'engenheiro';
    case Tecnico = 'tecnico';
    case Orcamentista = 'orcamentista';

    public function rotulo(): string
    {
        return match ($this) {
            self::Gestor => 'Gestor',
            self::Engenheiro => 'Engenheiro',
            self::Tecnico => 'Técnico',
            self::Orcamentista => 'Orçamentista',
        };
    }

    /** Quem pode o quê. Para mudar uma permissão, edite só aqui. */
    public function pode(string $acao): bool
    {
        return match ($acao) {
            'usuarios' => $this === self::Gestor,
            'relatorios', 'propostas' => in_array($this, [self::Gestor, self::Orcamentista], true),
            'excluir' => in_array($this, [self::Gestor, self::Engenheiro], true),
            'levantamento' => $this !== self::Orcamentista,
            default => false,
        };
    }
}