<x-layouts::app title="Novo usuário">
    <div class="w-full max-w-3xl">
        <h1 class="font-titulo mb-6 text-2xl font-bold text-sipra-azul dark:text-white">Novo usuário</h1>

        <form method="POST" action="{{ route('usuarios.store') }}"
              class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            @include('usuarios._form')
        </form>
    </div>
</x-layouts::app>