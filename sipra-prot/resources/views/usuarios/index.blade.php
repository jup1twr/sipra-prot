<x-layouts::app title="Usuários">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-titulo text-2xl font-bold text-sipra-azul dark:text-white">Usuários</h1>
                <p class="text-sm text-zinc-500">Quem tem acesso ao SIPRA na sua empresa.</p>
            </div>

            <flux:button variant="primary" icon="plus" :href="route('usuarios.create')">Novo usuário</flux:button>
        </div>

        @if (session('ok'))
            <div class="rounded-lg border border-sipra-verde/30 bg-sipra-verde/10 px-4 py-3 text-sm text-sipra-verde-escuro dark:text-emerald-300">
                {{ session('ok') }}
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-sipra-fundo text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3">Usuário</th>
                        <th class="px-4 py-3">Papel</th>
                        <th class="px-4 py-3">Situação</th>
                        <th class="px-4 py-3 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($usuarios as $u)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium text-sipra-azul dark:text-white">
                                    {{ $u->name }}
                                    @if ($u->is(auth()->user())) <span class="text-xs text-zinc-500">(você)</span> @endif
                                </div>
                                <div class="text-xs text-zinc-500">{{ $u->email }}</div>
                            </td>
                            <td class="px-4 py-3">{{ \App\Enums\Papel::tryFrom((string) $u->papel)?->rotulo() ?? $u->papel }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $u->ativo ? 'bg-sipra-verde/10 text-sipra-verde-escuro dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' }}">
                                    {{ $u->ativo ? 'Ativo' : 'Desativado' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <flux:button size="sm" :href="route('usuarios.edit', $u->id)">Editar</flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts::app>