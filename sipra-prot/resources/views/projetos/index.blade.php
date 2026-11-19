<x-layouts::app title="Meus Projetos">
    <script>
        function iniciarKanban(raiz) {
            if (raiz.dataset.pronto) return;
            if (!window.Sortable) {
                console.error('SortableJS não carregou. Reinicie o composer run dev.');
                return;
            }
            raiz.dataset.pronto = '1';

            const enviar = async (cartao, status) => {
                const resposta = await fetch(cartao.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': raiz.dataset.csrf,
                    },
                    body: JSON.stringify({ status }),
                });
                if (!resposta.ok) throw new Error('falhou');
            };

            const atualizarContagens = () => {
                raiz.querySelectorAll('[data-coluna]').forEach((coluna) => {
                    coluna.querySelector('[data-contagem]').textContent =
                        coluna.querySelectorAll('.kanban-card').length;
                });
            };

            raiz.querySelectorAll('.kanban-coluna').forEach((coluna) => {
                new Sortable(coluna, {
                    group: 'projetos',
                    animation: 150,
                    draggable: '.kanban-card',
                    ghostClass: 'opacity-40',
                    filter: 'select, a',
                    preventOnFilter: false,
                    onEnd: async (e) => {
                        if (e.from === e.to) return;
                        try {
                            await enviar(e.item, e.to.dataset.status);
                            e.item.querySelector('[data-mover]').value = e.to.dataset.status;
                        } catch (erro) {
                            e.from.insertBefore(e.item, e.from.children[e.oldIndex] || null);
                            alert('Não foi possível mover o projeto. Tente novamente.');
                        }
                        atualizarContagens();
                    },
                });
            });

            // Alternativa para celular: seletor de status no cartão
            raiz.querySelectorAll('[data-mover]').forEach((seletor) => {
                seletor.addEventListener('change', async () => {
                    const cartao = seletor.closest('.kanban-card');
                    const origem = cartao.parentElement;
                    const destino = raiz.querySelector(`.kanban-coluna[data-status="${seletor.value}"]`);
                    try {
                        await enviar(cartao, seletor.value);
                        destino.prepend(cartao);
                    } catch (erro) {
                        seletor.value = origem.dataset.status;
                        alert('Não foi possível mover o projeto. Tente novamente.');
                    }
                    atualizarContagens();
                });
            });
        }
    </script>

    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-titulo text-2xl font-bold text-sipra-azul dark:text-white">Meus Projetos</h1>
                <p class="text-sm text-zinc-500">Continue de onde parou ou abra um projeto novo.</p>
            </div>

            <div class="flex items-center gap-3">
                <div class="inline-flex overflow-hidden rounded-lg border border-zinc-300 text-sm">
                    <a href="{{ route('dashboard', array_filter(['busca' => $busca])) }}"
                       class="px-3 py-1.5 {{ $visao === 'lista' ? 'bg-sipra-azul text-white' : 'bg-white text-zinc-700 hover:bg-zinc-50' }}">Lista</a>
                    <a href="{{ route('dashboard', array_filter(['busca' => $busca, 'visao' => 'kanban'])) }}"
                       class="px-3 py-1.5 {{ $visao === 'kanban' ? 'bg-sipra-azul text-white' : 'bg-white text-zinc-700 hover:bg-zinc-50' }}">Kanban</a>
                </div>
                <flux:button variant="primary" icon="plus" :href="route('projetos.create')">Novo Projeto</flux:button>
            </div>
        </div>

        @if (session('ok'))
            <div class="rounded-lg border border-sipra-verde/30 bg-sipra-verde/10 px-4 py-3 text-sm text-sipra-verde-escuro dark:text-emerald-300">
                {{ session('ok') }}
            </div>
        @endif

        @if ($visao === 'lista')
            {{-- Filtros rápidos por status --}}
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('dashboard', array_filter(['busca' => $busca])) }}"
                   class="rounded-full border px-3 py-1 text-sm {{ ! $status ? 'border-sipra-azul bg-sipra-azul text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' }}">
                    Todos ({{ $contagens->sum() }})
                </a>
                @foreach (\App\Enums\StatusProjeto::cases() as $s)
                    <a href="{{ route('dashboard', array_filter(['status' => $s->value, 'busca' => $busca])) }}"
                       class="rounded-full border px-3 py-1 text-sm {{ $status === $s ? 'border-sipra-azul bg-sipra-azul text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' }}">
                        {{ $s->rotulo() }} ({{ $contagens[$s->value] ?? 0 }})
                    </a>
                @endforeach
            </div>
        @endif

        <form method="GET" class="flex flex-wrap items-center gap-3">
            @if ($visao === 'kanban')
                <input type="hidden" name="visao" value="kanban">
            @endif
            @if ($status)
                <input type="hidden" name="status" value="{{ $status->value }}">
            @endif
            <div class="w-full max-w-sm">
                <flux:input name="busca" :value="$busca" icon="magnifying-glass" placeholder="Buscar por projeto ou cliente" />
            </div>
            <flux:button type="submit">Buscar</flux:button>
            @if ($busca !== '' || $status)
                <flux:button variant="ghost" :href="route('dashboard', array_filter(['visao' => $visao === 'kanban' ? 'kanban' : null]))">Limpar</flux:button>
            @endif
        </form>

        @if ($visao === 'kanban')
            {{-- KANBAN --}}
            <div class="grid auto-cols-[18rem] grid-flow-col gap-4 overflow-x-auto pb-4 md:grid-flow-row md:auto-cols-auto md:grid-cols-4"
                 data-csrf="{{ csrf_token() }}" x-data x-init="iniciarKanban($el)">
                @foreach (\App\Enums\StatusProjeto::cases() as $s)
                    <div data-coluna class="flex min-w-0 flex-col rounded-xl bg-zinc-100 p-3 dark:bg-zinc-800/60">
                        <div class="mb-3 flex items-center justify-between">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $s->classes() }}">{{ $s->rotulo() }}</span>
                            <span class="text-xs text-zinc-500" data-contagem>{{ count($colunas[$s->value] ?? []) }}</span>
                        </div>

                        <div class="kanban-coluna flex min-h-32 flex-1 flex-col gap-3" data-status="{{ $s->value }}">
                            @foreach ($colunas[$s->value] ?? [] as $projeto)
                                @php
                                    $atrasado = $projeto->prazo && $projeto->prazo->isPast() && $projeto->status !== \App\Enums\StatusProjeto::Aprovado;
                                @endphp
                                <div class="kanban-card cursor-grab rounded-lg border border-zinc-200 bg-white p-3 shadow-sm active:cursor-grabbing dark:border-zinc-700 dark:bg-zinc-900"
                                     data-url="{{ route('projetos.status', $projeto) }}">
                                    <a href="{{ route('projetos.show', $projeto) }}" class="font-medium text-sipra-azul hover:underline dark:text-white">{{ $projeto->nome }}</a>
                                    <div class="mt-1 text-xs text-zinc-500">{{ $projeto->cliente?->nome ?? 'Sem cliente' }}</div>

                                    <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                                        <span class="{{ $atrasado ? 'font-semibold text-red-600' : 'text-zinc-500' }}">
                                            {{ $projeto->prazo ? $projeto->prazo->format('d/m/Y') : 'Sem prazo' }}{{ $atrasado ? ' (atrasado)' : '' }}
                                        </span>
                                        <span class="truncate text-zinc-500">{{ $projeto->responsavel?->name ?? '—' }}</span>
                                    </div>

                                    <select data-mover class="mt-2 w-full rounded border border-zinc-300 bg-white px-2 py-1 text-xs md:hidden dark:border-zinc-600 dark:bg-zinc-900">
                                        @foreach (\App\Enums\StatusProjeto::cases() as $opcao)
                                            <option value="{{ $opcao->value }}" @selected($projeto->status === $opcao)>{{ $opcao->rotulo() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-zinc-500">Arraste os cartões entre as colunas para mudar o status. Nos cartões, os projetos seguem a ordem do prazo.</p>
        @else
            {{-- LISTA --}}
            <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-sipra-fundo text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3">Projeto</th>
                            <th class="px-4 py-3">Cliente</th>
                            <th class="px-4 py-3">Responsável</th>
                            <th class="px-4 py-3">Prazo</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($projetos as $projeto)
                            @php
                                $atrasado = $projeto->prazo && $projeto->prazo->isPast() && $projeto->status !== \App\Enums\StatusProjeto::Aprovado;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium text-sipra-azul dark:text-white">
                                    <a href="{{ route('projetos.show', $projeto) }}" class="hover:underline">{{ $projeto->nome }}</a>
                                </td>
                                <td class="px-4 py-3">{{ $projeto->cliente?->nome ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $projeto->responsavel?->name ?? '—' }}</td>
                                <td class="px-4 py-3 {{ $atrasado ? 'font-semibold text-red-600' : '' }}">
                                    {{ $projeto->prazo?->format('d/m/Y') ?? '—' }}
                                    @if ($atrasado) <span class="text-xs">(atrasado)</span> @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $projeto->status->classes() }}">
                                        {{ $projeto->status->rotulo() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" :href="route('projetos.edit', $projeto)">Editar</flux:button>

                                        <form method="POST" action="{{ route('projetos.destroy', $projeto) }}"
                                              onsubmit="return confirm('Excluir o projeto {{ e($projeto->nome) }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <flux:button size="sm" variant="danger" type="submit">Excluir</flux:button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-zinc-500">
                                    Nenhum projeto encontrado. Ajuste os filtros ou crie um novo projeto.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $projetos->links() }}
        @endif
    </div>
</x-layouts::app>