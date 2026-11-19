@php
    $moeda = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $maxMes = max(1, $serieMeses->max('valor'));
    $maxProj = max(1, $projetosPorStatus->max() ?? 1);

    $cartoes = [
        ['Projetos ativos', (string) $projetosAtivos, '+'.$projetosNovos.' criados no período'],
        ['Orçamentos emitidos', (string) $quantidade, $nAprovadas.' aprovados · '.($decididas - $nAprovadas).' recusados'],
        ['Total orçado', $moeda($valorTotal), $periodoRotulo],
        ['Taxa de aprovação', $taxaAprovacao === null ? '—' : $taxaAprovacao.' %', $nAprovadas.' de '.$decididas.' decididos'],
    ];

    // Ordem do fluxo de trabalho, com as cores de cada status
    $statusProjeto = [
        'em_levantamento'      => ['Em Levantamento', '#2F6DB5'],
        'aguardando_orcamento' => ['Aguardando Orçamento', '#B7791F'],
        'em_revisao'           => ['Em Revisão', '#7B4FB5'],
        'aprovado'             => ['Aprovado', '#078c61'],
    ];

    $rotuloProposta = [
        'rascunho' => 'Rascunho', 'enviada' => 'Enviada',
        'aprovada' => 'Aprovada', 'recusada' => 'Recusada',
    ];

    $relatorios = [
        ['Orçamentos por período', 'Valores, BDI e status por revisão', 'PDF'],
        ['Produtividade por responsável', 'Projetos e prazos por integrante', 'XLSX'],
        ['Quantitativos por projeto', 'Áreas e volumes validados no levantamento', 'PDF'],
        ['Consumo de insumos', 'Itens mais utilizados e variação de preço', 'XLSX'],
    ];

    $selectClasse = 'rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900';
@endphp

<x-layouts::app title="Relatórios">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-titulo text-2xl font-bold text-sipra-azul dark:text-white">Relatórios</h1>
                <p class="text-sm text-zinc-500">Visão geral de projetos e propostas da sua empresa.</p>
            </div>

            <flux:button variant="primary" icon="arrow-down-tray" disabled title="Em breve">Exportar PDF</flux:button>
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-3">
            <select name="periodo" onchange="this.form.submit()" class="{{ $selectClasse }}">
                @foreach ($periodos as $chave => $rotulo)
                    <option value="{{ $chave }}" @selected($periodo === $chave)>Período: {{ $rotulo }}</option>
                @endforeach
            </select>

            <select name="responsavel" onchange="this.form.submit()" class="{{ $selectClasse }}">
                <option value="">Responsável: Todos</option>
                @foreach ($responsaveis as $r)
                    <option value="{{ $r->id }}" @selected($responsavelId === $r->id)>Responsável: {{ $r->name }}</option>
                @endforeach
            </select>

            @if ($periodo !== '6m' || $responsavelId)
                <flux:button variant="ghost" size="sm" :href="route('relatorios.index')">Limpar filtros</flux:button>
            @endif
        </form>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cartoes as [$rotulo, $valor, $nota])
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $rotulo }}</div>
                    <div class="font-titulo mt-2 text-3xl font-bold text-sipra-azul dark:text-white">{{ $valor }}</div>
                    <div class="mt-1 text-xs font-medium text-sipra-verde-escuro dark:text-emerald-300">{{ $nota }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-titulo mb-4 font-semibold text-sipra-azul dark:text-white">Valor orçado por mês</h2>
                <div class="flex items-end gap-3 overflow-x-auto">
                    @foreach ($serieMeses as $mes)
                        <div class="flex min-w-14 flex-1 flex-col items-center justify-end gap-2">
                            <span class="text-xs font-semibold text-sipra-azul dark:text-white">{{ $mes['valor'] > 0 ? number_format($mes['valor'] / 1000, 1, ',', '.').' mil' : '—' }}</span>
                            <div class="w-full rounded-t-md {{ $mes['atual'] ? 'bg-sipra-verde' : 'bg-sipra-azul dark:bg-sky-700' }}"
                                 style="height: {{ max(4, round($mes['valor'] / $maxMes * 160)) }}px"></div>
                            <span class="text-xs text-zinc-500">{{ $mes['rotulo'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-titulo mb-4 font-semibold text-sipra-azul dark:text-white">Projetos por status</h2>
                @foreach ($statusProjeto as $chave => [$rotulo, $cor])
                    @php $qtd = (int) ($projetosPorStatus[$chave] ?? 0); @endphp
                    <div class="mb-4">
                        <div class="mb-1 flex justify-between text-sm">
                            <span>{{ $rotulo }}</span>
                            <b>{{ $qtd }}</b>
                        </div>
                        <div class="h-2 rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-2 rounded-full" style="width: {{ $qtd / $maxProj * 100 }}%; background-color: {{ $cor }}"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-titulo p-5 pb-3 font-semibold text-sipra-azul dark:text-white">Propostas por status</h2>
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-zinc-200 bg-sipra-fundo text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-2">Status</th><th class="px-5 py-2 text-right">Qtd.</th><th class="px-5 py-2 text-right">Valor</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($porStatus as $linha)
                            <tr>
                                <td class="px-5 py-3">{{ $rotuloProposta[$linha->status] ?? \Illuminate\Support\Str::headline($linha->status) }}</td>
                                <td class="px-5 py-3 text-right">{{ $linha->qtd }}</td>
                                <td class="px-5 py-3 text-right">{{ $moeda($linha->valor) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-zinc-500">Nenhuma proposta neste filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-titulo p-5 pb-3 font-semibold text-sipra-azul dark:text-white">Maiores clientes</h2>
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-zinc-200 bg-sipra-fundo text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr><th class="px-5 py-2">Cliente</th><th class="px-5 py-2 text-right">Propostas</th><th class="px-5 py-2 text-right">Valor</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($topClientes as $linha)
                            <tr>
                                <td class="px-5 py-3 font-medium text-sipra-azul dark:text-white">{{ $linha->nome }}</td>
                                <td class="px-5 py-3 text-right">{{ $linha->qtd }}</td>
                                <td class="px-5 py-3 text-right">{{ $moeda($linha->valor) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-zinc-500">Sem dados neste filtro.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="font-titulo p-5 pb-3 font-semibold text-sipra-azul dark:text-white">Relatórios disponíveis</h2>
            <div class="divide-y divide-zinc-200 border-t border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                @foreach ($relatorios as [$nome, $descricao, $formato])
                    <div class="flex flex-wrap items-center gap-4 px-5 py-3">
                        <div class="min-w-48 flex-1">
                            <div class="text-sm font-semibold text-sipra-azul dark:text-white">{{ $nome }}</div>
                            <div class="text-xs text-zinc-500">{{ $descricao }}</div>
                        </div>
                        <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-500 dark:bg-zinc-800">{{ $formato }}</span>
                        <flux:button size="sm" disabled title="Em breve">Exportar</flux:button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts::app>