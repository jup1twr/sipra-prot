<?php

namespace App\Http\Controllers;

use App\Enums\StatusProposta;
use App\Models\Projeto;
use App\Models\Proposta;
use App\Services\GeradorProposta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropostaController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->input('busca'));
        $status = StatusProposta::tryFrom((string) $request->input('status'));

        $propostas = Proposta::query()
            ->with('projeto.cliente')
            ->when($busca !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('numero', 'like', "%{$busca}%")
                ->orWhereHas('projeto', fn ($p) => $p->where('nome', 'like', "%{$busca}%"))))
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->orderByDesc('ano')
            ->orderByDesc('sequencia')
            ->paginate(15)
            ->withQueryString();

        $contagens = Proposta::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('propostas.index', compact('propostas', 'busca', 'status', 'contagens'));
    }

    public function store(Request $request, Projeto $projeto, GeradorProposta $gerador)
    {
        $dados = $request->validate(
            ['validade_dias' => ['required', 'integer', 'min:1', 'max:180']],
            ['validade_dias.*' => 'Informe a validade em dias (de 1 a 180).']
        );

        try {
            $proposta = $gerador->gerar($projeto, (int) $dados['validade_dias']);
        } catch (\DomainException $e) {
            return redirect()->route('projetos.orcamento', $projeto->id)
                ->withErrors(['orcamento' => $e->getMessage()]);
        }

        return redirect()->route('propostas.index')
            ->with('ok', "Proposta {$proposta->numero} criada como rascunho.");
    }

    public function pdf(Request $request, Proposta $proposta)
    {
        $proposta->load('projeto.cliente', 'projeto.responsavel');

        $empresa = $request->user()->empresa;

        $arquivoLogo = public_path('img/sipra-logo-completo.png');
        $logo = file_exists($arquivoLogo) ? str_replace('\\', '/', $arquivoLogo) : null;

        $pdf = Pdf::loadView('propostas.pdf', compact('proposta', 'empresa', 'logo'))->setPaper('a4');

        $nome = 'proposta-'.str_replace('/', '-', $proposta->numero).'.pdf';

        return $request->boolean('baixar') ? $pdf->download($nome) : $pdf->stream($nome);
    }

    public function mudarStatus(Request $request, Proposta $proposta)
    {
        $dados = $request->validate([
            'status' => ['required', Rule::enum(StatusProposta::class)],
        ]);

        $proposta->update(['status' => $dados['status']]);

        return back()->with('ok', "Proposta {$proposta->numero} atualizada.");
    }

    public function destroy(Proposta $proposta)
    {
        if ($proposta->status !== StatusProposta::Rascunho) {
            return back()->withErrors(['proposta' => 'Só é possível excluir propostas em rascunho.']);
        }

        $proposta->delete(); // exclusão segura; o número não é reaproveitado

        return back()->with('ok', "Proposta {$proposta->numero} excluída.");
    }
}