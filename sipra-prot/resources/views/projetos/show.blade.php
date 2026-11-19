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
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
    <h2 class="font-titulo mb-4 font-semibold text-sipra-azul dark:text-white">Plantas e anexos</h2>

    <form method="POST" action="{{ route('projetos.anexos.store', $projeto) }}" enctype="multipart/form-data"
          class="mb-6 flex flex-wrap items-center gap-3">
        @csrf
        <input type="file" name="arquivos[]" multiple required
               class="block w-full max-w-md text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-sipra-fundo file:px-3 file:py-2 file:text-sm file:font-medium">
        <flux:button variant="primary" type="submit" icon="arrow-up-tray">Enviar</flux:button>
    </form>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $erro)
                <div>{{ $erro }}</div>
            @endforeach
        </div>
    @endif

    <p class="mb-3 text-xs text-zinc-500">PDF, imagens, DWG/DXF, Word, Excel ou ZIP. Até 20 MB por arquivo e 10 por envio.</p>
                @forelse ($projeto->anexos as $anexo)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-200 py-3 text-sm first:border-t-0 dark:border-zinc-700">
                        <div class="min-w-0">
                            <a href="{{ route('anexos.show', $anexo) }}" target="_blank" class="font-medium text-sipra-azul hover:underline dark:text-white">
                                {{ $anexo->nome_original }}
                            </a>
                            <div class="text-xs text-zinc-500">
                                {{ \Illuminate\Support\Number::fileSize($anexo->tamanho, precision: 1) }}
                                · {{ $anexo->autor?->name ?? '—' }}
                                · {{ $anexo->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <flux:button size="sm" :href="route('anexos.show', ['anexo' => $anexo, 'baixar' => 1])">Baixar</flux:button>

                            <form method="POST" action="{{ route('anexos.destroy', $anexo) }}"
                                onsubmit="return confirm('Remover o arquivo {{ e($anexo->nome_original) }}?')">
                                @csrf
                                @method('DELETE')
                                <flux:button size="sm" variant="danger" type="submit">Remover</flux:button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500">Nenhum arquivo enviado ainda.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts::app>