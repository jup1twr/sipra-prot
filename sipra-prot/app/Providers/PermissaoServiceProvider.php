<?php

namespace App\Providers;

use App\Enums\Papel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PermissaoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (['usuarios', 'relatorios', 'propostas', 'excluir', 'levantamento'] as $acao) {
            Gate::define($acao, fn ($user) => Papel::tryFrom((string) $user->papel)?->pode($acao) ?? false);
        }
    }
}