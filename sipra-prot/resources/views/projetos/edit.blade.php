<x-layouts::app title="Editar projeto">
    <div class="w-full max-w-3xl">
        <h1 class="font-titulo mb-6 text-2xl font-bold text-sipra-azul dark:text-white">Editar projeto</h1>

        <form method="POST" action="{{ route('projetos.update', $projeto) }}"
              class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @method('PUT')
            @include('projetos._form')
        </form>
    </div>
</x-layouts::app>