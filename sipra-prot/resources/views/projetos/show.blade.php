<x-layouts::app :title="$projeto->nome">
    <div class="flex w-full flex-1 flex-col gap-6">
        @include('projetos._cabecalho')

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                <h2 class="font-titulo mb-3 font-semibold text-sipra-azul dark:text-white">Briefing</h2>
                @if ($projeto->briefing)
                    <p class="whitespace-pre-line text-sm">{{ $projeto->briefing }}</p>
                @else
                    <p class="text-sm text-zinc-500">Nenhum briefing informado. Use "Editar projeto" para adicionar.</p>
                @endif
            </div>

            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="font-titulo mb-3 font-semibold text-sipra-azul dark:text-white">Dados do projeto</h2>
                <dl class="space-y-3 text-sm">
                    <div><dt class="text-zinc-500">Cliente</dt><dd class="font-medium">{{ $projeto->cliente?->nome ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">Responsável</dt><dd class="font-medium">{{ $projeto->responsavel?->name ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">Prazo</dt><dd class="font-medium">{{ $projeto->prazo?->format('d/m/Y') ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">Criado em</dt><dd class="font-medium">{{ $projeto->created_at->format('d/m/Y H:i') }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center dark:border-zinc-600 dark:bg-zinc-900">
            <p class="font-medium text-sipra-azul dark:text-white">Plantas e anexos</p>
            <p class="mt-1 text-sm text-zinc-500">O envio de arquivos entra no próximo passo.</p>
        </div>
    </div>
</x-layouts::app>