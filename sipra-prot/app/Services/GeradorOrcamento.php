<?php

namespace App\Services;

use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Projeto;
use Illuminate\Support\Facades\DB;

class GeradorOrcamento
{
    public function __construct(private CalculadoraQuantitativo $calculadora)
    {
    }

    /**
     * Cria o orçamento (se não existir) e atualiza as quantidades a partir do levantamento.
     * Preços já editados são mantidos; insumos novos entram com o preço atual (congelado).
     *
     * @return array{0: Orcamento, 1: int} o orçamento e quantas linhas inconsistentes foram ignoradas
     */
    public function sincronizar(Projeto $projeto): array
    {
        return DB::transaction(function () use ($projeto) {
            $orcamento = Orcamento::firstOrCreate(['projeto_id' => $projeto->id]);

            $quantidades = [];
            $insumos = [];
            $ignorados = 0;

            $ambientes = $projeto->ambientes()->with('itens.insumo')->get();

            foreach ($ambientes as $ambiente) {
                foreach ($ambiente->itens as $item) {
                    if ($this->calculadora->inconsistente($item)) {
                        $ignorados++;
                        continue;
                    }

                    $id = $item->insumo_id;
                    $quantidades[$id] = ($quantidades[$id] ?? 0) + $this->calculadora->total($item);
                    $insumos[$id] = $item->insumo;
                }
            }

            $linhas = OrcamentoItem::where('orcamento_id', $orcamento->id)->get()->keyBy('insumo_id');

            foreach ($quantidades as $insumoId => $quantidade) {
                $quantidade = round($quantidade, 3);

                if ($linha = $linhas->get($insumoId)) {
                    $linha->update(['quantidade' => $quantidade]);
                    continue;
                }

                $insumo = $insumos[$insumoId];

                $orcamento->itens()->create([
                    'insumo_id' => $insumo->id,
                    'descricao' => $insumo->descricao,
                    'unidade' => $insumo->tipo_medida->unidade(),
                    'quantidade' => $quantidade,
                    'preco_material' => $insumo->preco_material,   // preço congelado
                    'preco_mao_obra' => $insumo->preco_mao_obra,   // preço congelado
                ]);
            }

            // Insumos que saíram do levantamento deixam o orçamento (exclusão segura)
            OrcamentoItem::where('orcamento_id', $orcamento->id)
                ->whereNotIn('insumo_id', array_keys($quantidades))
                ->delete();

            return [$orcamento, $ignorados];
        });
    }

    /** Totais do orçamento. A mesma conta é refeita na tela, ao vivo. */
    public function totais(Orcamento $orcamento): array
    {
        $perdas = (float) $orcamento->perdas_percentual;
        $bdi = (float) $orcamento->bdi_percentual;

        $materiais = 0.0;
        $maoDeObra = 0.0;

        foreach ($orcamento->itens as $item) {
            $quantidade = (float) $item->quantidade;
            $materiais += round($quantidade * (float) $item->preco_material * (1 + $perdas / 100), 2);
            $maoDeObra += round($quantidade * (float) $item->preco_mao_obra, 2);
        }

        $custoDireto = $materiais + $maoDeObra;
        $valorBdi = round($custoDireto * $bdi / 100, 2);

        return [
            'materiais' => round($materiais, 2),
            'mao_de_obra' => round($maoDeObra, 2),
            'custo_direto' => round($custoDireto, 2),
            'bdi' => $valorBdi,
            'total' => round($custoDireto + $valorBdi, 2),
        ];
    }
}