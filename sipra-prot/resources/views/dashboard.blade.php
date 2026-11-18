<x-layouts::app title="Meus Projetos">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-titulo text-2xl font-bold text-sipra-azul dark:text-white">Meus Projetos</h1>
                <p class="text-sm text-zinc-500">Continue de onde parou ou abra um projeto novo.</p>
            </div>

            <flux:button variant="primary" icon="plus" href="#">Novo Projeto</flux:button>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <p class="font-medium text-sipra-azul dark:text-white">Nenhum projeto ainda</p>
            <p class="mt-1 text-sm text-zinc-500">Quando o módulo de projetos estiver pronto, eles aparecerão aqui.</p>
        </div>
    </div>
</x-layouts::app>