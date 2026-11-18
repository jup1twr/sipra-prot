<?php

namespace App\Models\Concerns;

trait PertenceAEmpresa
{
    protected static function bootPertenceAEmpresa(): void
    {
        // Ao consultar: mostra só os registros da empresa do usuário logado
        static::addGlobalScope('empresa', function ($consulta) {
            if (auth()->check()) {
                $consulta->where(
                    $consulta->getModel()->getTable().'.empresa_id',
                    auth()->user()->empresa_id
                );
            }
        });

        // Ao criar: grava a empresa do usuário logado automaticamente
        static::creating(function ($modelo) {
            if (auth()->check() && ! $modelo->empresa_id) {
                $modelo->empresa_id = auth()->user()->empresa_id;
            }
        });
    }
}