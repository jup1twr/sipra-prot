<?php

namespace App\Http\Controllers;

use App\Enums\StatusProjeto;
use App\Http\Requests\ProjetoRequest;
use App\Models\Cliente;
use App\Models\Projeto;
use App\Models\User;
use Illuminate\Http\Request;

class ProjetoController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->input('busca'));
        $visao = $request->input('visao') === 'kanban' ? 'kanban' : 'lista';
        $status = $visao === 'lista' ? StatusProjeto::tryFrom((string) $request->input('status')) : null;

        $consulta = Projeto::query()
            ->with(['cliente', 'responsavel'])
            ->when($busca !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nome', 'like', "%{$busca}%")
                ->orWhereHas('cliente', fn ($c) => $c->where('nome', 'like', "%{$busca}%"))))
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->orderByRaw('prazo is null, prazo');

        $projetos = $visao === 'lista' ? $consulta->paginate(15)->withQueryString() : null;
        $colunas = $visao === 'kanban'
            ? $consulta->limit(300)->get()->groupBy(fn ($p) => $p->status->value)
            : collect();

        $contagens = Projeto::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('projetos.index', compact('projetos', 'busca', 'status', 'contagens', 'visao', 'colunas'));
    }

    public function create(Request $request)
    {
        $projeto = new Projeto([
            'status' => StatusProjeto::EmLevantamento,
            'responsavel_id' => auth()->id(),
            'cliente_id' => $request->input('cliente_id'),
        ]);

        return view('projetos.create', ['projeto' => $projeto] + $this->opcoes());
    }

    public function store(ProjetoRequest $request)
    {
        $projeto = Projeto::create($request->validated());

        return redirect()->route('projetos.show', $projeto)->with('ok', 'Projeto criado.');
    }

    public function update(ProjetoRequest $request, Projeto $projeto)
    {
        $projeto->update($request->validated());

        return redirect()->route('projetos.show', $projeto)->with('ok', 'Projeto atualizado.');
    }

    public function edit(Projeto $projeto)
    {
        return view('projetos.edit', ['projeto' => $projeto] + $this->opcoes());
    }

    public function destroy(Projeto $projeto)
    {
        $projeto->delete();

        return redirect()->route('dashboard')->with('ok', 'Projeto excluído.');
    }

    public function show(Projeto $projeto)
    {
        $projeto->load(['cliente', 'responsavel', 'anexos.autor']);

        return view('projetos.show', compact('projeto'));
    }

    public function levantamento(Projeto $projeto)
    {
        return $this->abaProvisoria($projeto, 'Levantamento de Medidas');
    }

    public function orcamento(Projeto $projeto)
    {
        return $this->abaProvisoria($projeto, 'Orçamento');
    }

    public function historico(Projeto $projeto)
    {
        return $this->abaProvisoria($projeto, 'Histórico');
    }

    private function abaProvisoria(Projeto $projeto, string $titulo)
    {
        $projeto->load(['cliente', 'responsavel']);

        return view('projetos.aba-provisoria', compact('projeto', 'titulo'));
    }   

    public function mudarStatus(Request $request, Projeto $projeto)
    {
        $dados = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::enum(StatusProjeto::class)],
        ]);

        $projeto->update(['status' => $dados['status']]);

        return response()->json(['ok' => true, 'status' => $projeto->status->value]);
    }

    private function opcoes(): array
    {
        return [
            'clientes' => Cliente::orderBy('nome')->get(),
            'responsaveis' => User::where('empresa_id', auth()->user()->empresa_id)->orderBy('name')->get(),
        ];
    }
}