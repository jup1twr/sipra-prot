<x-layouts::app :title="'Levantamento - '.$projeto->nome">
    <script>
        function planilha(linhasIniciais, insumos) {
            const num = (v) => parseFloat(String(v ?? '').replace(',', '.'));
            const campos = {
                area: ['comprimento', 'largura'],
                volume: ['comprimento', 'largura', 'altura'],
                linear: ['comprimento'],
                unidade: ['quantidade'],
            };

            return {
                insumos,
                linhas: linhasIniciais,
                seq: 0,

                init() {
                    this.linhas.forEach((l) => (l._k = ++this.seq));
                    if (!this.linhas.length) this.nova();
                },
                nova() {
                    this.linhas.push({
                        _k: ++this.seq, id: null, insumo_id: '',
                        comprimento: '', largura: '', altura: '', quantidade: '', observacao: '',
                    });
                },
                remover(i) { this.linhas.splice(i, 1); },

                tipo(l) { return this.insumos[l.insumo_id]?.tipo ?? null; },
                unidade(l) { return this.insumos[l.insumo_id]?.unidade ?? ''; },
                usa(l, campo) { return (campos[this.tipo(l)] ?? []).includes(campo); },

                total(l) {
                    const t = this.tipo(l);
                    if (!t || this.problema(l)) return 0;
                    const c = num(l.comprimento), la = num(l.largura), a = num(l.altura), q = num(l.quantidade);
                    if (t === 'area') return c * la;
                    if (t === 'volume') return c * la * a;
                    if (t === 'linear') return c;
                    return q;
                },
                problema(l) {
                    const t = this.tipo(l);
                    if (!t) return 'Escolha o insumo';
                    for (const campo of campos[t]) {
                        const v = num(l[campo]);
                        if (isNaN(v) || v <= 0) return 'Medida faltando ou zerada';
                        if (v > (campo === 'quantidade' ? 100000 : 500)) return 'Medida absurda: confira o valor';
                    }
                    return null;
                },
                somas() {
                    const s = {};
                    this.linhas.forEach((l) => {
                        const t = this.tipo(l);
                        if (t && !this.problema(l)) s[this.unidade(l)] = (s[this.unidade(l)] ?? 0) + this.total(l);
                    });
                    return s;
                },
                comProblema() { return this.linhas.filter((l) => this.problema(l)).length; },
                fmt(v) {
                    return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 3 }).format(v);
                },
            };
        }
    </script>

    <div class="flex w-full flex-1 flex-col gap-6">
        @include('projetos._cabecalho')

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                @foreach (array_unique($errors->all()) as $erro)
                    <div>{{ $erro }}</div>
                @endforeach
            </div>
        @endif

        {{-- Ambientes --}}
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($ambientes as $a)
                <a href="{{ route('projetos.levantamento', [$projeto, 'ambiente' => $a->id]) }}"
                   class="rounded-full border px-3 py-1 text-sm {{ $ambiente?->id === $a->id ? 'border-sipra-azul bg-sipra-azul text-white' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50' }}">
                    {{ $a->nome }}
                </a>
            @endforeach

            <form method="POST" action="{{ route('projetos.ambientes.store', $projeto) }}" class="flex items-center gap-2">
                @csrf
                <input name="nome" required maxlength="100" placeholder="Novo ambiente (ex.: Cozinha)"
                       class="rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                <flux:button size="sm" type="submit" icon="plus">Adicionar</flux:button>
            </form>
        </div>

        @if ($ambiente)
            <form id="excluir-ambiente" method="POST" action="{{ route('ambientes.destroy', $ambiente) }}"
                  onsubmit="return confirm('Excluir o ambiente {{ e($ambiente->nome) }} e todas as suas linhas?')">
                @csrf
                @method('DELETE')
            </form>

            <form method="POST" action="{{ route('ambientes.itens.salvar', $ambiente) }}"
                  x-data="planilha(@js($linhas), @js($insumosJs))"
                  class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                @csrf
                @method('PUT')

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <h2 class="font-titulo font-semibold text-sipra-azul dark:text-white">{{ $ambiente->nome }}</h2>
                    <button type="submit" form="excluir-ambiente" class="text-sm text-red-600 hover:underline">Excluir ambiente</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-sipra-fundo text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                            <tr>
                                <th class="min-w-64 px-3 py-2">Insumo</th>
                                <th class="px-3 py-2">Compr. (m)</th>
                                <th class="px-3 py-2">Larg. (m)</th>
                                <th class="px-3 py-2">Alt. (m)</th>
                                <th class="px-3 py-2">Qtd.</th>
                                <th class="px-3 py-2 text-right">Total</th>
                                <th class="px-3 py-2">Observação</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            <template x-for="(l, i) in linhas" :key="l._k">
                                <tr :class="problema(l) && l.insumo_id ? 'bg-red-50 dark:bg-red-950/30' : ''">
                                    <td class="px-3 py-2 align-top">
                                        <input type="hidden" :name="`itens[${i}][id]`" :value="l.id ?? ''">
                                        <select :name="`itens[${i}][insumo_id]`" x-model="l.insumo_id" required
                                                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                            <option value="">Selecione o insumo...</option>
                                            @foreach ($insumos->groupBy('categoria') as $categoria => $grupo)
                                                <optgroup label="{{ $categoria ?: 'Sem categoria' }}">
                                                    @foreach ($grupo as $insumo)
                                                        <option value="{{ $insumo->id }}">{{ $insumo->descricao }} ({{ $insumo->tipo_medida->unidade() }})</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        <div x-show="l.insumo_id && problema(l)" x-cloak class="mt-1 text-xs font-medium text-red-600"
                                             x-text="'⚠ ' + problema(l)"></div>
                                    </td>

                                    @foreach (['comprimento', 'largura', 'altura', 'quantidade'] as $campo)
                                        <td class="px-3 py-2 align-top">
                                            <input type="number" step="any" min="0"
                                                   :name="`itens[${i}][{{ $campo }}]`" x-model="l.{{ $campo }}"
                                                   :disabled="!usa(l, '{{ $campo }}')"
                                                   class="w-24 rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm tabular-nums disabled:bg-zinc-100 disabled:text-zinc-400 dark:border-zinc-600 dark:bg-zinc-900 dark:disabled:bg-zinc-800">
                                        </td>
                                    @endforeach

                                    <td class="whitespace-nowrap px-3 py-2 text-right align-top font-semibold tabular-nums text-sipra-azul dark:text-white"
                                        x-text="tipo(l) ? fmt(total(l)) + ' ' + unidade(l) : '—'"></td>

                                    <td class="px-3 py-2 align-top">
                                        <input type="text" maxlength="255" :name="`itens[${i}][observacao]`" x-model="l.observacao"
                                               class="w-full min-w-40 rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-900">
                                    </td>

                                    <td class="px-3 py-2 align-top">
                                        <button type="button" @click="remover(i)" class="text-lg leading-none text-zinc-400 hover:text-red-600" title="Remover linha">&times;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <div class="flex flex-wrap items-center gap-4 text-sm">
                        <span class="text-zinc-500">Total do ambiente:</span>
                        <template x-for="(valor, unidade) in somas()" :key="unidade">
                            <span class="font-semibold text-sipra-azul dark:text-white" x-text="fmt(valor) + ' ' + unidade"></span>
                        </template>
                        <span x-show="comProblema() > 0" x-cloak class="font-medium text-red-600"
                              x-text="'⚠ ' + comProblema() + ' linha(s) para revisar'"></span>
                    </div>

                    <div class="flex gap-3">
                        <flux:button type="button" icon="plus" @click="nova()">Adicionar linha</flux:button>
                        <flux:button variant="primary" type="submit">Salvar levantamento</flux:button>
                    </div>
                </div>
            </form>

            <p class="text-xs text-zinc-500">As alterações só são gravadas ao clicar em "Salvar levantamento".</p>
        @else
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center dark:border-zinc-600 dark:bg-zinc-900">
                <p class="font-medium text-sipra-azul dark:text-white">Nenhum ambiente ainda</p>
                <p class="mt-1 text-sm text-zinc-500">Crie o primeiro ambiente acima (por exemplo, Cozinha) para lançar as medidas.</p>
            </div>
        @endif
    </div>
</x-layouts::app>