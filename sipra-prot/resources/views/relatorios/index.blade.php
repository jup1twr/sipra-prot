@php
    $moeda = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $maxMes = max(1, $serieMeses->max('valor'));
    $maxProj = max(1, $projetosPorStatus->max('qtd') ?? 1);

    $cartoes = [
        ['Valor orçado', $moeda($valorTotal), $quantidade.' proposta(s)'],
        ['Valor aprovado', $moeda($valorAprovado), 'propostas aprovadas'],
        ['Taxa de aprovação', $taxaAprovacao === null ? '—' : $taxaAprovacao.'%', 'aprovadas ÷ decididas'],
        ['Ticket médio', $moeda($ticketMedio), 'valor médio por proposta'],
    ];
@endphp

<x-layouts::app title="Relatórios">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <h1 class="font-titulo text-2xl font-bold text-sipra-azul dark:text-white">Relatórios</h1>
            <p class="text-sm text-zinc-500">Visão geral de projetos e propostas da sua empresa.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cartoes as [$rotulo, $valor, $nota])
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="text-sm text-zinc-500">{{ $rotulo }}</div>
                    <div class="font-titulo mt-1 text-2xl font-bold text-sipra-azul dark:text-white">{{ $valor }}</div>
                    <div class="mt-1 text-xs text-zinc-500">{{ $nota }}</div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 lg:col-span-2 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-titulo mb-4 font-semibold text-sipra-azul dark:text-white">Valor orçado por mês</h2>
                <div class="flex items-end gap-3 overflow-x-auto">
                    @foreach ($serieMeses as $mes)
                        <div class="flex min-w-14 flex-1 flex-col items-center justify-end gap-2">
                            <span class="text-xs text-zinc-500">{{ $mes['valor'] > 0 ? number_format($mes['valor'] / 1000, 1, ',', '.').' mil' : '—' }}</span>
                            <div class="w-full rounded-t-md bg-sipra-verde" style="height: {{ max(4, round($mes['valor'] / $maxMes * 140)) }}px"></div>
                            <span class="text-xs text-zinc-500">{{ $mes['rotulo'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-titulo mb-4 font-semibold text-sipra-azul dark:text-white">Projetos por status</h2>
                @forelse ($projetosPorStatus as $linha)
                    <div class="mb-3">
                        <div class="mb-1 flex justify-between text-sm">
                            <span>{{ \Illuminate\Support\Str::headline($linha->status) }}</span>
                            <b>{{ $linha->qtd }}</b>
                        </div>
                        <div class="h-2 rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-2 rounded-full bg-sipra-verde" style="width: {{ $linha->qtd / $maxProj * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">Nenhum projeto cadastrado ainda.</p>
                @endforelse
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
                                <td class="px-5 py-3">{{ \Illuminate\Support\Str::headline($linha->status) }}</td>
                                <td class="px-5 py-3 text-right">{{ $linha->qtd }}</td>
                                <td class="px-5 py-3 text-right">{{ $moeda($linha->valor) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-zinc-500">Nenhuma proposta ainda.</td></tr>
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
                            <tr><td colspan="3" class="px-5 py-8 text-center text-zinc-500">Sem dados ainda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts::app>