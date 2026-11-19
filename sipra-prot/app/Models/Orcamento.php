<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Orcamento extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $fillable = ['projeto_id', 'perdas_percentual', 'bdi_percentual'];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(OrcamentoItem::class)->orderBy('descricao');
    }
}