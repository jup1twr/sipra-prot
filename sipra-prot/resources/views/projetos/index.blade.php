<x-layouts::app title="Meus Projetos">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-titulo text-2xl font-bold text-sipra-azul dark:text-white">Meus Projetos</h1>
                <p class="text-sm text-zinc-500">Continue de onde parou ou abra um projeto novo.</p>
            </div>

            <flux:button variant="primary" icon="plus" :href="route('projetos.create')">Novo Projeto</flux:button>
        </div>

        @if (session('ok'))
            <div class="rounded-lg border border-sipra-verde/30 bg-sipra-verde/10 px-4 py-3 text-sm text-sipra-verde-escuro dark:text-emerald-300">
                {{ session('ok') }}
            </div>
        @endif

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

        <form method="GET" class="flex flex-wrap items-center gap-3">
            @if ($status)
                <input type="hidden" name="status" value="{{ $status->value }}">
            @endif
            <div class="w-full max-w-sm">
                <flux:input name="busca" :value="$busca" icon="magnifying-glass" placeholder="Buscar por projeto ou cliente" />
            </div>
            <flux:button type="submit">Buscar</flux:button>
            @if ($busca !== '' || $status)
                <flux:button variant="ghost" :href="route('dashboard')">Limpar</flux:button>
            @endif
        </form>

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
                            <td class="px-4 py-3 font-medium text-sipra-azul dark:text-white"> <a href="{{ route('projetos.show', $projeto) }}" class="hover:underline">{{ $projeto->nome }}</a> </td>
                            
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
    </div>
</x-layouts::app>