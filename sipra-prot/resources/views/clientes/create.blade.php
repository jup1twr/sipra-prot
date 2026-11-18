<x-layouts::app title="Novo cliente">
    <div class="w-full max-w-3xl">
        <h1 class="font-titulo mb-6 text-2xl font-bold text-sipra-azul dark:text-white">Novo cliente</h1>

        <form method="POST" action="{{ route('clientes.store') }}"
              class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('clientes._form')
        </form>
    </div>
</x-layouts::app>