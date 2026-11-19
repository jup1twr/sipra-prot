<?php

namespace App\Models;

use App\Enums\StatusProjeto;
use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Projeto extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $fillable = ['cliente_id', 'responsavel_id', 'nome', 'status', 'prazo', 'briefing'];

    protected function casts(): array
    {
        return [
            'status' => StatusProjeto::class,
            'prazo' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class)->withTrashed();
    }

    public function responsavel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_id');
    }
    
    public function anexos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Anexo::class)->latest();
    }
}