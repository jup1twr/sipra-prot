<?php

namespace App\Models;

use App\Models\Concerns\PertenceAEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Anexo extends Model
{
    use PertenceAEmpresa, SoftDeletes;

    protected $fillable = ['projeto_id', 'enviado_por', 'nome_original', 'caminho', 'mime', 'tamanho'];

    public function projeto(): BelongsTo
    {
        return $this->belongsTo(Projeto::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function ehImagem(): bool
    {
        return str_starts_with((string) $this->mime, 'image/');
    }
}