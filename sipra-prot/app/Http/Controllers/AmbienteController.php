<?php

namespace App\Http\Controllers;

use App\Models\Ambiente;
use App\Models\ItemLevantamento;
use App\Models\Projeto;
use Illuminate\Http\Request;

class AmbienteController extends Controller
{
    public function store(Request $request, Projeto $projeto)
    {
        $dados = $request->validate(
            ['nome' => ['required', 'string', 'max:100']],
            ['nome.required' => 'Informe o nome do ambiente.']
        );

        $ordem = ((int) Ambiente::where('projeto_id', $projeto->id)->max('ordem')) + 1;

        $ambiente = $projeto->ambientes()->create(['nome' => $dados['nome'], 'ordem' => $ordem]);

        return redirect()
            ->route('projetos.levantamento', [$projeto->id, 'ambiente' => $ambiente->id])
            ->with('ok', 'Ambiente criado.');
    }

    public function destroy(Ambiente $ambiente)
    {
        $projetoId = $ambiente->projeto_id;

        ItemLevantamento::where('ambiente_id', $ambiente->id)->delete();
        $ambiente->delete(); // exclusão segura

        return redirect()->route('projetos.levantamento', $projetoId)->with('ok', 'Ambiente excluído.');
    }
}