<x-layouts::app title="Clientes">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-titulo text-2xl font-bold text-sipra-azul dark:text-white">Clientes</h1>
                <p class="text-sm text-zinc-500">{{ $clientes->total() }} cliente(s) encontrado(s)</p>
            </div>

            <flux:button variant="primary" icon="plus" :href="route('clientes.create')">Novo cliente</flux:button>
        </div>

        @if (session('ok'))
            <div class="rounded-lg border border-sipra-verde/30 bg-sipra-verde/10 px-4 py-3 text-sm text-sipra-verde-escuro dark:text-emerald-300">
                {{ session('ok') }}
            </div>
        @endif

        <form method="GET" class="flex flex-wrap items-center gap-3">
            <div class="w-full max-w-sm">
                <flux:input name="busca" :value="$busca" icon="magnifying-glass" placeholder="Buscar por nome, documento ou cidade" />
            </div>

            <select name="tipo" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                <option value="">Todos os tipos</option>
                <option value="PF" @selected($tipo === 'PF')>Pessoa física</option>
                <option value="PJ" @selected($tipo === 'PJ')>Pessoa jurídica</option>
            </select>

            <flux:button type="submit">Buscar</flux:button>

            @if ($busca !== '' || $tipo)
                <flux:button variant="ghost" :href="route('clientes.index')">Limpar</flux:button>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-sipra-fundo text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Documento</th>
                        <th class="px-4 py-3">Telefone</th>
                        <th class="px-4 py-3">Cidade</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($clientes as $cliente)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-sipra-azul dark:text-white">{{ $cliente->nome }}</div>
                                <div class="text-xs text-zinc-500">{{ $cliente->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $cliente->tipo === 'PJ' ? 'bg-sipra-azul/10 text-sipra-azul dark:bg-white/10 dark:text-white' : 'bg-sipra-verde/10 text-sipra-verde-escuro dark:text-emerald-300' }}">
                                    {{ $cliente->tipo }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $cliente->documento ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $cliente->telefone ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $cliente->cidade ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" :href="route('clientes.edit', $cliente)">Editar</flux:button>

                                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}"
                                          onsubmit="return confirm('Excluir o cliente {{ e($cliente->nome) }}?')">
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
                                Nenhum cliente encontrado. Ajuste a busca ou cadastre um novo cliente.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $clientes->links() }}
    </div>
</x-layouts::app>