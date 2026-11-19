<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrcamentoItem extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $table = 'orcamento_itens';

    protected $fillable = ['insumo_id', 'descricao', 'unidade', 'quantidade', 'preco_material', 'preco_mao_obra'];

    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class);
    }
}