<x-layouts::app :title="$titulo.' - '.$projeto->nome">
    <div class="flex w-full flex-1 flex-col gap-6">
        @include('projetos._cabecalho')

        <div class="rounded-xl border border-zinc-200 bg-white p-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <p class="font-medium text-sipra-azul dark:text-white">{{ $titulo }}</p>
            <p class="mt-1 text-sm text-zinc-500">Esta aba será construída nos próximos módulos.</p>
        </div>
    </div>
</x-layouts::app>