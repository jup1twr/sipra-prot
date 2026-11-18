<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $fillable = ['nome', 'tipo', 'documento', 'telefone', 'email', 'cidade'];
}