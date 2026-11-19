<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use App\Models\OrcamentoItem;
use App\Models\Projeto;
use App\Services\GeradorOrcamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrcamentoController extends Controller
{
    public function show(Projeto $projeto, GeradorOrcamento $gerador)
    {
        $projeto->load(['cliente', 'responsavel']);

        $orcamento = $projeto->orcamento()->with('itens')->first();
        $totais = $orcamento ? $gerador->totais($orcamento) : null;

        $itensJs = $orcamento
            ? $orcamento->itens->map(fn ($i) => [
                'id' => $i->id,
                'descricao' => $i->descricao,
                'unidade' => $i->unidade,
                'quantidade' => (float) $i->quantidade,
                'preco_material' => (float) $i->preco_material,
                'preco_mao_obra' => (float) $i->preco_mao_obra,
            ])->values()->all()
            : [];

        return view('projetos.orcamento', compact('projeto', 'orcamento', 'totais', 'itensJs'));
    }

    /** Gera o orçamento ou atualiza as quantidades a partir do levantamento. */
    public function gerar(Projeto $projeto, GeradorOrcamento $gerador)
    {
        [, $ignorados] = $gerador->sincronizar($projeto);

        $mensagem = 'Orçamento atualizado com as quantidades do levantamento.';
        if ($ignorados > 0) {
            $mensagem .= " {$ignorados} linha(s) com medida inconsistente foram ignoradas.";
        }

        return redirect()->route('projetos.orcamento', $projeto->id)->with('ok', $mensagem);
    }

    public function atualizar(Request $request, Orcamento $orcamento)
    {
        $dados = $request->validate([
            'perdas_percentual' => ['required', 'numeric', 'min:0', 'max:100'],
            'bdi_percentual' => ['required', 'numeric', 'min:0', 'max:300'],
            'itens' => ['nullable', 'array'],
            'itens.*.preco_material' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'itens.*.preco_mao_obra' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ], [
            'perdas_percentual.numeric' => 'Informe as perdas em porcentagem (0 a 100).',
            'perdas_percentual.max' => 'As perdas não podem passar de 100%.',
            'bdi_percentual.numeric' => 'Informe o BDI em porcentagem.',
            'bdi_percentual.max' => 'O BDI não pode passar de 300%.',
            'itens.*.preco_material.numeric' => 'Use apenas números nos preços.',
            'itens.*.preco_mao_obra.numeric' => 'Use apenas números nos preços.',
            'itens.*.preco_material.required' => 'Preencha todos os preços (use 0 se não houver).',
            'itens.*.preco_mao_obra.required' => 'Preencha todos os preços (use 0 se não houver).',
        ]);

        DB::transaction(function () use ($orcamento, $dados) {
            $orcamento->update([
                'perdas_percentual' => $dados['perdas_percentual'],
                'bdi_percentual' => $dados['bdi_percentual'],
            ]);

            foreach ($dados['itens'] ?? [] as $id => $precos) {
                OrcamentoItem::where('orcamento_id', $orcamento->id)->whereKey($id)->update([
                    'preco_material' => $precos['preco_material'],
                    'preco_mao_obra' => $precos['preco_mao_obra'],
                ]);
            }
        });

        return redirect()->route('projetos.orcamento', $orcamento->projeto_id)->with('ok', 'Orçamento salvo.');
    }
}