<?php

namespace App\Http\Controllers;

use App\Exports\ProdutividadeExport;
use App\Services\FiltroRelatorio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExportacaoRelatorioController extends Controller
{
    public function exportar(Request $request, string $tipo)
    {
        $filtro = FiltroRelatorio::daRequisicao($request);

        return match ($tipo) {
            'resumo'        => $this->resumo($filtro),
            'orcamentos'    => $this->orcamentos($filtro),
            'produtividade' => $this->produtividade($filtro),
            default         => abort(404),
        };
    }

    private function resumo(FiltroRelatorio $filtro)
    {
        $dados = $filtro->resumo() + [
            'titulo'          => 'Relatório geral',
            'filtroDescricao' => $filtro->descricao(),
        ];

        return Pdf::loadView('relatorios.pdf.resumo', $dados)
            ->setPaper('a4')
            ->download('relatorio-geral.pdf');
    }

    private function orcamentos(FiltroRelatorio $filtro)
    {
        $linhas = $filtro->propostas()
            ->join('clientes', 'clientes.id', '=', 'projetos.cliente_id')
            ->select(
                'propostas.numero', 'propostas.status', 'propostas.valor_total',
                'propostas.validade_ate', 'propostas.created_at',
                'projetos.nome as projeto', 'clientes.nome as cliente'
            )
            ->orderByDesc('propostas.created_at')
            ->get();

        return Pdf::loadView('relatorios.pdf.orcamentos', [
            'titulo'          => 'Orçamentos por período',
            'filtroDescricao' => $filtro->descricao(),
            'linhas'          => $linhas,
        ])->setPaper('a4', 'landscape')->download('orcamentos-por-periodo.pdf');
    }

    private function produtividade(FiltroRelatorio $filtro)
    {
        $proj = $filtro->projetos()
            ->select(
                DB::raw('coalesce(responsavel_id, 0) as resp'),
                DB::raw('count(*) as total'),
                DB::raw("sum(status = 'em_levantamento') as levantamento"),
                DB::raw("sum(status = 'aguardando_orcamento') as aguardando"),
                DB::raw("sum(status = 'em_revisao') as revisao"),
                DB::raw("sum(status = 'aprovado') as aprovados"),
                DB::raw("sum(status != 'aprovado' and prazo is not null and prazo < curdate()) as vencidos")
            )
            ->groupBy('resp')
            ->get()
            ->keyBy('resp');

        $props = $filtro->propostas()
            ->select(
                DB::raw('coalesce(projetos.responsavel_id, 0) as resp'),
                DB::raw('count(*) as qtd'),
                DB::raw("sum(propostas.status like 'aprov%') as aprovadas"),
                DB::raw('sum(propostas.valor_total) as valor')
            )
            ->groupBy('resp')
            ->get()
            ->keyBy('resp');

        $pessoas = DB::table('users')
            ->where('empresa_id', $filtro->empresaId)
            ->when($filtro->responsavelId, fn ($q, $id) => $q->where('id', $id))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($u) => [(int) $u->id, $u->name]);

        if (! $filtro->responsavelId && ($proj->has(0) || $props->has(0))) {
            $pessoas->push([0, 'Sem responsável']);
        }

        $linhas = $pessoas->map(function ($pessoa) use ($proj, $props) {
            [$id, $nome] = $pessoa;
            $p = $proj->get($id);
            $q = $props->get($id);

            return [
                $nome,
                (int) ($p?->total ?? 0),
                (int) ($p?->levantamento ?? 0),
                (int) ($p?->aguardando ?? 0),
                (int) ($p?->revisao ?? 0),
                (int) ($p?->aprovados ?? 0),
                (int) ($p?->vencidos ?? 0),
                (int) ($q?->qtd ?? 0),
                (int) ($q?->aprovadas ?? 0),
                (float) ($q?->valor ?? 0),
            ];
        })->values();

        return Excel::download(new ProdutividadeExport($linhas), 'produtividade-por-responsavel.xlsx');
    }
}