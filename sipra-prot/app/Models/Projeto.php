<?php

namespace App\Models;

use App\Enums\StatusProjeto;
use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Tipologia;

class Projeto extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $fillable = ['cliente_id', 'responsavel_id', 'nome', 'status', 'tipologia', 'prazo', 'briefing'];

    protected function casts(): array
    {
        return [
            'status' => StatusProjeto::class,
            'tipologia' => Tipologia::class,
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

    public function ambientes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ambiente::class)->orderBy('ordem')->orderBy('id');
    }

    public function orcamento(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Orcamento::class);
    }
}