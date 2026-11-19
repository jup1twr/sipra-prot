<?php

namespace App\Models;

use App\Enums\StatusProposta;
use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proposta extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $fillable = [
        'projeto_id', 'orcamento_id', 'ano', 'sequencia', 'numero',
        'status', 'validade_ate', 'valor_total', 'itens', 'condicoes',
    ];

    protected function casts(): array
    {
        return [
            'status' => StatusProposta::class,
            'validade_ate' => 'date',
            'valor_total' => 'decimal:2',
            'itens' => 'array',
        ];
    }

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class)->withTrashed();
    }
}