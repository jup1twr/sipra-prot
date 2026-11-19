<x-layouts::app :title="'Orçamento - '.$projeto->nome">
    <script>
        function orcamento(itensIniciais, perdasInicial, bdiInicial) {
            const n = (v) => {
                const x = parseFloat(String(v ?? '').replace(',', '.'));
                return isNaN(x) ? 0 : x;
            };
            const r2 = (v) => Math.round((v + Number.EPSILON) * 100) / 100;

            return {
                itens: itensIniciais,
                perdas: perdasInicial,
                bdi: bdiInicial,

                material(i) { return r2(n(i.quantidade) * n(i.preco_material) * (1 + n(this.perdas) / 100)); },
                maoObra(i) { return r2(n(i.quantidade) * n(i.preco_mao_obra)); },
                linha(i) { return this.material(i) + this.maoObra(i); },

                get totalMateriais() { return this.itens.reduce((s, i) => s + this.material(i), 0); },
                get totalMaoObra() { return this.itens.reduce((s, i) => s + this.maoObra(i), 0); },
                get custoDireto() { return this.totalMateriais + this.totalMaoObra; },
                get valorBdi() { return r2(this.custoDireto * n(this.bdi) / 100); },
                get total() { return r2(this.custoDireto + this.valorBdi); },

                moeda(v) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(v); },
                qtd(v) { return new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 3 }).format(v); },
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

        @if (! $orcamento)
            <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-10 text-center dark:border-zinc-600 dark:bg-zinc-900">
                <p class="font-medium text-sipra-azul dark:text-white">Este projeto ainda não tem orçamento</p>
                <p class="mx-auto mt-1 max-w-md text-sm text-zinc-500">
                    O orçamento é montado a partir das medidas lançadas no Levantamento. Os preços vêm dos insumos e ficam congelados no orçamento.
                </p>
                <form method="POST" action="{{ route('projetos.orcamento.gerar', $projeto) }}" class="mt-6">
                    @csrf
                    <flux:button variant="primary" type="submit" icon="calculator">Gerar orçamento a partir do levantamento</flux:button>
                </form>
            </div>
        @else
            <form id="form-atualizar" method="POST" action="{{ route('projetos.orcamento.gerar', $projeto) }}"
                  onsubmit="return confirm('Atualizar as quantidades a partir do levantamento? Alterações de preço ainda não salvas serão perdidas.')">
                @csrf
            </form>

            <form id="form-proposta" method="POST" action="{{ route('projetos.propostas.store', $projeto) }}">
                @csrf
            </form>

            <form method="POST" action="{{ route('orcamentos.atualizar', $orcamento) }}"
                  x-data="orcamento(@js($itensJs), @js((float) $orcamento->perdas_percentual), @js((float) $orcamento->bdi_percentual))"
                  class="grid gap-6 lg:grid-cols-3 lg:items-start">
                @csrf
                @method('PUT')

                {{-- Composição de custos --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900 lg:col-span-2">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                        <h2 class="font-titulo font-semibold text-sipra-azul dark:text-white">Composição de custos</h2>
                        <button type="submit" form="form-atualizar" class="text-sm text-sipra-verde-escuro hover:underline">
                            Atualizar quantidades do levantamento
                        </button>
                    </div>

                    @if (count($itensJs) === 0)
                        <p class="px-4 py-10 text-center text-sm text-zinc-500">
                            Nenhuma medida válida no levantamento ainda. Lance as medidas na aba Levantamento e depois atualize as quantidades.
                        </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-sipra-fundo text-xs uppercase text-zinc-500 dark:bg-zinc-800">
                                    <tr>
                                        <th class="min-w-56 px-4 py-2.5">Item</th>
                                        <th class="px-4 py-2.5 text-right">Quantidade</th>
                                        <th class="px-4 py-2.5">Material (R$/un)</th>
                                        <th class="px-4 py-2.5">Mão de obra (R$/un)</th>
                                        <th class="px-4 py-2.5 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    <template x-for="i in itens" :key="i.id">
                                        <tr>
                                            <td class="px-4 py-3 font-medium text-sipra-azul dark:text-white" x-text="i.descricao"></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums" x-text="qtd(i.quantidade) + ' ' + i.unidade"></td>
                                            <td class="px-4 py-3">
                                                <input type="number" step="0.01" min="0" required
                                                       :name="`itens[${i.id}][preco_material]`" x-model="i.preco_material"
                                                       class="w-28 rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm tabular-nums focus:border-sipra-verde focus:outline-none focus:ring-2 focus:ring-sipra-verde/30 dark:border-zinc-600 dark:bg-zinc-900">
                                            </td>
                                            <td class="px-4 py-3">
                                                <input type="number" step="0.01" min="0" required
                                                       :name="`itens[${i.id}][preco_mao_obra]`" x-model="i.preco_mao_obra"
                                                       class="w-28 rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm tabular-nums focus:border-sipra-verde focus:outline-none focus:ring-2 focus:ring-sipra-verde/30 dark:border-zinc-600 dark:bg-zinc-900">
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right font-semibold tabular-nums text-sipra-azul dark:text-white"
                                                x-text="moeda(linha(i))"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Coluna da direita: parâmetros + resumo --}}
                <div class="flex flex-col gap-4 lg:sticky lg:top-6">
                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <h2 class="font-titulo text-base font-semibold text-sipra-azul dark:text-white">Parâmetros</h2>
                        <p class="mt-1 text-xs text-zinc-500">Valem para todos os itens do orçamento.</p>

                        <div class="mt-5 grid grid-cols-2 gap-4">
                            <div>
                                <label for="perdas" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-zinc-500">Perdas de material</label>
                                <div class="relative">
                                    <input id="perdas" type="number" step="0.01" min="0" max="100" name="perdas_percentual" x-model="perdas" required
                                           class="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-3 pr-8 text-right text-sm tabular-nums focus:border-sipra-verde focus:outline-none focus:ring-2 focus:ring-sipra-verde/30 dark:border-zinc-600 dark:bg-zinc-900">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-zinc-400">%</span>
                                </div>
                            </div>

                            <div>
                                <label for="bdi" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-zinc-500">BDI / margem</label>
                                <div class="relative">
                                    <input id="bdi" type="number" step="0.01" min="0" max="300" name="bdi_percentual" x-model="bdi" required
                                           class="w-full rounded-lg border border-zinc-300 bg-white py-2 pl-3 pr-8 text-right text-sm tabular-nums focus:border-sipra-verde focus:outline-none focus:ring-2 focus:ring-sipra-verde/30 dark:border-zinc-600 dark:bg-zinc-900">
                                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-zinc-400">%</span>
                                </div>
                            </div>
                        </div>

                        <p class="mt-4 text-xs leading-relaxed text-zinc-500">
                            As perdas afetam só os materiais. O BDI incide sobre o custo direto (materiais + mão de obra).
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <h2 class="font-titulo text-base font-semibold text-sipra-azul dark:text-white">Resumo financeiro</h2>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-zinc-500">Materiais <span class="text-xs">(com perdas)</span></dt>
                                <dd class="font-medium tabular-nums" x-text="moeda(totalMateriais)"></dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-zinc-500">Mão de obra</dt>
                                <dd class="font-medium tabular-nums" x-text="moeda(totalMaoObra)"></dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-4 border-t border-dashed border-zinc-200 pt-3 dark:border-zinc-700">
                                <dt class="text-zinc-500">Custo direto</dt>
                                <dd class="font-medium tabular-nums" x-text="moeda(custoDireto)"></dd>
                            </div>
                            <div class="flex items-baseline justify-between gap-4">
                                <dt class="text-zinc-500">BDI <span class="text-xs" x-text="'(' + bdi + '%)'"></span></dt>
                                <dd class="font-medium tabular-nums" x-text="moeda(valorBdi)"></dd>
                            </div>
                        </dl>

                        <div class="mt-5 rounded-lg bg-sipra-verde/10 px-4 py-3">
                            <div class="text-xs font-medium uppercase tracking-wide text-sipra-verde-escuro dark:text-emerald-300">Total do orçamento</div>
                            <div class="font-titulo mt-1 text-2xl font-bold tabular-nums text-sipra-azul dark:text-white" x-text="moeda(total)"></div>
                        </div>

                        <div class="mt-5 flex flex-col gap-2">
                            <flux:button variant="primary" type="submit" class="w-full">Salvar orçamento</flux:button>
                            <div class="mt-2 border-t border-dashed border-zinc-200 pt-4 dark:border-zinc-700">
                                <label for="validade_dias" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-zinc-500">Validade da proposta (dias)</label>
                                <input id="validade_dias" form="form-proposta" type="number" name="validade_dias" value="15" min="1" max="180" required
                                    class="mb-3 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm tabular-nums focus:border-sipra-verde focus:outline-none focus:ring-2 focus:ring-sipra-verde/30 dark:border-zinc-600 dark:bg-zinc-900">
                                <button type="submit" form="form-proposta"
                                        class="w-full rounded-lg border border-sipra-azul bg-white px-3 py-2 text-sm font-medium text-sipra-azul hover:bg-sipra-azul hover:text-white">
                                    Gerar proposta em PDF
                                </button>
                                <p class="mt-2 text-xs text-zinc-500">A proposta usa o orçamento <strong>salvo</strong>. Salve antes de gerar.</p>
                            </div>
                        </div>

                        <p class="mt-3 text-center text-xs text-zinc-500">As alterações só são gravadas ao clicar em "Salvar orçamento".</p>
                    </div>
                </div>
            </form>
        @endif
    </div>
</x-layouts::app>