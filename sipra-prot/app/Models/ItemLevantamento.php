<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemLevantamento extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $table = 'itens_levantamento';

    protected $fillable = ['ambiente_id', 'insumo_id', 'comprimento', 'largura', 'altura', 'quantidade', 'observacao'];

    public function ambiente(): BelongsTo
    {
        return $this->belongsTo(Ambiente::class);
    }

    public function insumo(): BelongsTo
    {
        return $this->belongsTo(Insumo::class)->withTrashed();
    }
}