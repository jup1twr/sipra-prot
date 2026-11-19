<x-layouts::app title="Propostas">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div>
            <h1 class="font-titulo text-2xl font-bold text-sipra-azul dark:text-white">Propostas</h1>
            <p class="text-sm text-zinc-500">Para criar uma proposta, abra o orçamento de um projeto e clique em "Gerar proposta".</p>
        </div>

        @if (session('ok'))
            <div class="rounded-lg border border-sipra-verde/30 bg-sipra-verde/10 px-4 py-3 text-sm text-sipra-verde-escuro dark:text-emerald-300">
                {{ session('ok') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach (array_unique($errors->all()) as $erro)
                    <div>{{ $erro }}</div>
                @endforeach
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('propostas.index', array_filter(['busca' => $busca])) }}"
               class="rounded-full border px-3 py-1 text-sm {{ ! $status ? 'border-sipra-azul bg-sipra-azul text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' }}">
                Todas ({{ $contagens->sum() }})
            </a>
            @foreach (\App\Enums\StatusProposta::cases() as $s)
                <a href="{{ route('propostas.index', array_filter(['status' => $s->value, 'busca' => $busca])) }}"
                   class="rounded-full border px-3 py-1 text-sm {{ $status === $s ? 'border-sipra-azul bg-sipra-azul text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' }}">
                    {{ $s->rotulo() }} ({{ $contagens[$s->value] ?? 0 }})
                </a>
            @endforeach
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-3">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status->value }}">
            @endif
            <div class="w-full max-w-sm">
                <flux:input name="busca" :value="$busca" icon="magnifying-glass" placeholder="Buscar por número ou projeto" />
            </div>
            <flux:button type="submit">Buscar</flux:button>
            @if ($busca !== '' || $status)
                <flux:button variant="ghost" :href="route('propostas.index')">Limpar</flux:button>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-sipra-fundo text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3">Nº</th>
                        <th class="px-4 py-3">Projeto / Cliente</th>
                        <th class="px-4 py-3 text-right">Valor</th>
                        <th class="px-4 py-3">Validade</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($propostas as $proposta)
                        @php
                            $vencida = $proposta->validade_ate->isPast()
                                && in_array($proposta->status, [\App\Enums\StatusProposta::Rascunho, \App\Enums\StatusProposta::Enviada], true);
                        @endphp
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-sipra-azul dark:text-white">{{ $proposta->numero }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $proposta->projeto?->nome ?? '—' }}</div>
                                <div class="text-xs text-zinc-500">{{ $proposta->projeto?->cliente?->nome ?? '—' }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-medium tabular-nums">
                                R$ {{ number_format((float) $proposta->valor_total, 2, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 {{ $vencida ? 'font-semibold text-red-600' : '' }}">
                                {{ $proposta->validade_ate->format('d/m/Y') }}
                                @if ($vencida) <span class="text-xs">(vencida)</span> @endif
                            </td>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('propostas.status', $proposta) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" onchange="this.form.submit()"
                                            class="rounded-full border-0 px-2 py-1 text-xs font-semibold {{ $proposta->status->classes() }}">
                                        @foreach (\App\Enums\StatusProposta::cases() as $opcao)
                                            <option value="{{ $opcao->value }}" @selected($proposta->status === $opcao)>{{ $opcao->rotulo() }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="primary" :href="route('propostas.pdf', $proposta)" target="_blank">PDF</flux:button>
                                    <flux:button size="sm" :href="route('propostas.pdf', [$proposta, 'baixar' => 1])">Baixar</flux:button>

                                    @if ($proposta->status === \App\Enums\StatusProposta::Rascunho)
                                        <form method="POST" action="{{ route('propostas.destroy', $proposta) }}"
                                              onsubmit="return confirm('Excluir a proposta {{ $proposta->numero }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <flux:button size="sm" variant="danger" type="submit">Excluir</flux:button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500">
                                Nenhuma proposta ainda. Gere uma a partir do orçamento de um projeto.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $propostas->links() }}
    </div>
</x-layouts::app>