<?php

namespace App\Http\Controllers;

use App\Models\Ambiente;
use App\Models\Insumo;
use App\Models\ItemLevantamento;
use App\Models\Projeto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LevantamentoController extends Controller
{
    public function index(Request $request, Projeto $projeto)
    {
        $projeto->load(['cliente', 'responsavel']);

        $ambientes = $projeto->ambientes;
        $ambiente = $ambientes->firstWhere('id', (int) $request->input('ambiente')) ?? $ambientes->first();

        $insumos = Insumo::orderBy('categoria')->orderBy('descricao')->get();

        $insumosJs = (object) $insumos->mapWithKeys(fn ($i) => [
            $i->id => [
                'descricao' => $i->descricao,
                'tipo' => $i->tipo_medida->value,
                'unidade' => $i->tipo_medida->unidade(),
            ],
        ])->all();

        $numero = fn ($v) => $v === null ? '' : (string) (float) $v;

        $linhas = $ambiente
            ? $ambiente->itens()->get()->map(fn ($i) => [
                'id' => $i->id,
                'insumo_id' => (string) $i->insumo_id,
                'comprimento' => $numero($i->comprimento),
                'largura' => $numero($i->largura),
                'altura' => $numero($i->altura),
                'quantidade' => $numero($i->quantidade),
                'observacao' => (string) $i->observacao,
            ])->values()->all()
            : [];

        return view('projetos.levantamento', compact('projeto', 'ambientes', 'ambiente', 'insumos', 'insumosJs', 'linhas'));
    }

    public function salvar(Request $request, Ambiente $ambiente)
    {
        $empresa = $request->user()->empresa_id;

        $dados = $request->validate([
            'itens' => ['nullable', 'array', 'max:500'],
            'itens.*.id' => ['nullable', 'integer'],
            'itens.*.insumo_id' => ['required', Rule::exists('insumos', 'id')
                ->where('empresa_id', $empresa)->whereNull('deleted_at')],
            'itens.*.comprimento' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'itens.*.largura' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'itens.*.altura' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'itens.*.quantidade' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'itens.*.observacao' => ['nullable', 'string', 'max:255'],
        ], [
            'itens.max' => 'Máximo de 500 linhas por ambiente.',
            'itens.*.insumo_id.required' => 'Escolha o insumo em todas as linhas.',
            'itens.*.insumo_id.exists' => 'Insumo inválido.',
            'itens.*.comprimento.numeric' => 'Use apenas números nas medidas.',
            'itens.*.largura.numeric' => 'Use apenas números nas medidas.',
            'itens.*.altura.numeric' => 'Use apenas números nas medidas.',
            'itens.*.quantidade.numeric' => 'Use apenas números nas medidas.',
        ]);

        $linhas = $dados['itens'] ?? [];

        DB::transaction(function () use ($ambiente, $linhas) {
            $existentes = ItemLevantamento::where('ambiente_id', $ambiente->id)->pluck('id')->all();
            $mantidos = [];

            foreach ($linhas as $linha) {
                $campos = [
                    'insumo_id' => $linha['insumo_id'],
                    'comprimento' => $linha['comprimento'] ?? null,
                    'largura' => $linha['largura'] ?? null,
                    'altura' => $linha['altura'] ?? null,
                    'quantidade' => $linha['quantidade'] ?? null,
                    'observacao' => $linha['observacao'] ?? null,
                ];

                $id = (int) ($linha['id'] ?? 0);

                if ($id && in_array($id, $existentes, true)) {
                    ItemLevantamento::where('ambiente_id', $ambiente->id)->whereKey($id)->update($campos);
                    $mantidos[] = $id;
                } else {
                    $mantidos[] = $ambiente->itens()->create($campos)->id;
                }
            }

            // Linhas removidas na tela: exclusão segura
            ItemLevantamento::where('ambiente_id', $ambiente->id)->whereNotIn('id', $mantidos)->delete();
        });

        return redirect()
            ->route('projetos.levantamento', [$ambiente->projeto_id, 'ambiente' => $ambiente->id])
            ->with('ok', 'Levantamento salvo.');
    }
}