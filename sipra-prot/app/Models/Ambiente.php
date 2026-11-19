<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ambiente extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $fillable = ['projeto_id', 'nome', 'ordem'];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function itens(): HasMany
    {
        return $this->hasMany(ItemLevantamento::class)->orderBy('id');
    }
}