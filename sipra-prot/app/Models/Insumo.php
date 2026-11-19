<?php

namespace App\Models;

use App\Enums\TipoMedida;
use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Insumo extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $fillable = ['descricao', 'categoria', 'tipo_medida', 'preco_material', 'preco_mao_obra'];

    protected function casts(): array
    {
        return ['tipo_medida' => TipoMedida::class];
    }
}