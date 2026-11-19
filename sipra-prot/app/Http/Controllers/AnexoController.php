<?php

namespace App\Http\Controllers;

use App\Models\Anexo;
use App\Models\Projeto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnexoController extends Controller
{
    public function store(Request $request, Projeto $projeto)
    {
        $request->validate([
            'arquivos' => ['required', 'array', 'max:10'],
            'arquivos.*' => ['file', 'max:20480', 'extensions:pdf,jpg,jpeg,png,webp,dwg,dxf,doc,docx,xls,xlsx,zip'],
        ], [
            'arquivos.required' => 'Escolha pelo menos um arquivo.',
            'arquivos.max' => 'Envie no máximo 10 arquivos por vez.',
            'arquivos.*.max' => 'Cada arquivo pode ter no máximo 20 MB.',
            'arquivos.*.extensions' => 'Tipo de arquivo não permitido.',
            'arquivos.*.file' => 'Arquivo inválido.',
        ]);

        foreach ($request->file('arquivos') as $arquivo) {
            $caminho = $arquivo->store("empresas/{$projeto->empresa_id}/projetos/{$projeto->id}");

            $projeto->anexos()->create([
                'enviado_por' => auth()->id(),
                'nome_original' => $arquivo->getClientOriginalName(),
                'caminho' => $caminho,
                'mime' => $arquivo->getClientMimeType(),
                'tamanho' => $arquivo->getSize(),
            ]);
        }

        return redirect()->route('projetos.show', $projeto)->with('ok', 'Arquivo(s) enviado(s).');
    }

    public function show(Request $request, Anexo $anexo)
    {
        abort_unless(Storage::exists($anexo->caminho), 404);

        return $request->boolean('baixar')
            ? Storage::download($anexo->caminho, $anexo->nome_original)
            : Storage::response($anexo->caminho, $anexo->nome_original);
    }

    public function destroy(Anexo $anexo)
    {
        $projeto = $anexo->projeto_id;
        $anexo->delete(); // exclusão segura: o registro some, o arquivo fica guardado

        return redirect()->route('projetos.show', $projeto)->with('ok', 'Arquivo removido.');
    }
}