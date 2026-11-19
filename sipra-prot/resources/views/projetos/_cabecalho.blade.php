@php
    $abas = [
        ['rota' => 'projetos.show', 'nome' => 'Resumo e Arquivos'],
        ['rota' => 'projetos.levantamento', 'nome' => 'Levantamento de Medidas'],
        ['rota' => 'projetos.orcamento', 'nome' => 'Orçamento'],
        ['rota' => 'projetos.historico', 'nome' => 'Histórico'],
    ];
@endphp

<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:text-sipra-azul">&larr; Meus Projetos</a>
        <h1 class="font-titulo mt-1 text-2xl font-bold text-sipra-azul dark:text-white">{{ $projeto->nome }}</h1>
        <p class="text-sm text-zinc-500">
            {{ $projeto->cliente?->nome ?? 'Sem cliente' }}
            <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold {{ $projeto->status->classes() }}">{{ $projeto->status->rotulo() }}</span>
        </p>
    </div>

    <flux:button :href="route('projetos.edit', $projeto)">Editar projeto</flux:button>
</div>

@if (session('ok'))
    <div class="rounded-lg border border-sipra-verde/30 bg-sipra-verde/10 px-4 py-3 text-sm text-sipra-verde-escuro dark:text-emerald-300">
        {{ session('ok') }}
    </div>
@endif

<nav class="flex gap-1 overflow-x-auto border-b border-zinc-200 dark:border-zinc-700">
    @foreach ($abas as $aba)
        <a href="{{ route($aba['rota'], $projeto) }}"
           class="whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium {{ request()->routeIs($aba['rota']) ? 'border-sipra-verde text-sipra-verde-escuro' : 'border-transparent text-zinc-500 hover:text-sipra-azul' }}">
            {{ $aba['nome'] }}
        </a>
    @endforeach
</nav>