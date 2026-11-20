<?php

namespace App\Http\Controllers;

use App\Exports\ConsumoInsumosExport;
use App\Exports\ProdutividadeExport;
use App\Services\FiltroRelatorio;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ExportacaoRelatorioController extends Controller
{
    /** Quantidade de um item, conforme o tipo de medida do insumo */
    private const QUANTIDADE_SQL = "case insumos.tipo_medida
        when 'area' then coalesce(itens_levantamento.comprimento, 0) * coalesce(itens_levantamento.largura, 0)
        when 'volume' then coalesce(itens_levantamento.comprimento, 0) * coalesce(itens_levantamento.largura, 0) * coalesce(itens_levantamento.altura, 0)
        else coalesce(itens_levantamento.quantidade, 0)
    end";

    public function exportar(Request $request, string $tipo)
    {
        $filtro = FiltroRelatorio::daRequisicao($request);

        return match ($tipo) {
            'resumo'        => $this->resumo($filtro),
            'orcamentos'    => $this->orcamentos($filtro),
            'produtividade' => $this->produtividade($filtro),
            'quantitativos' => $this->quantitativos($filtro),
            'insumos'       => $this->insumos($filtro),
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

    /** Itens do levantamento da empresa, com os filtros de responsável, tipologia e período */
    private function itensLevantamento(FiltroRelatorio $filtro): Builder
    {
        return DB::table('itens_levantamento')
            ->join('ambientes', 'ambientes.id', '=', 'itens_levantamento.ambiente_id')
            ->join('projetos', 'projetos.id', '=', 'ambientes.projeto_id')
            ->join('insumos', 'insumos.id', '=', 'itens_levantamento.insumo_id')
            ->where('itens_levantamento.empresa_id', $filtro->empresaId)
            ->whereNull('itens_levantamento.deleted_at')
            ->whereNull('ambientes.deleted_at')
            ->whereNull('projetos.deleted_at')
            ->when($filtro->responsavelId, fn ($q) => $q->where('projetos.responsavel_id', $filtro->responsavelId))
            ->when($filtro->tipologia, fn ($q) => $q->where('projetos.tipologia', $filtro->tipologia))
            ->when($filtro->inicio(), fn ($q, $inicio) => $q->where('itens_levantamento.created_at', '>=', $inicio));
    }

    private function quantitativos(FiltroRelatorio $filtro)
    {
        $itens = $this->itensLevantamento($filtro)
            ->join('clientes', 'clientes.id', '=', 'projetos.cliente_id')
            ->select(
                'projetos.id as projeto_id',
                'projetos.nome as projeto',
                'clientes.nome as cliente',
                'ambientes.nome as ambiente',
                'insumos.descricao as insumo',
                'insumos.tipo_medida as tipo',
                'itens_levantamento.comprimento',
                'itens_levantamento.largura',
                'itens_levantamento.altura',
                'itens_levantamento.quantidade as qtd_informada',
                'itens_levantamento.observacao',
                DB::raw(self::QUANTIDADE_SQL.' as quantidade')
            )
            ->orderBy('projetos.nome')
            ->orderBy('ambientes.ordem')
            ->orderBy('ambientes.id')
            ->orderBy('itens_levantamento.id')
            ->get();

        return Pdf::loadView('relatorios.pdf.quantitativos', [
            'titulo'          => 'Quantitativos por projeto',
            'filtroDescricao' => $filtro->descricao(),
            'projetos'        => $itens->groupBy('projeto_id'),
        ])->setPaper('a4')->download('quantitativos-por-projeto.pdf');
    }

    private function insumos(FiltroRelatorio $filtro)
    {
        $consumo = $this->itensLevantamento($filtro)
            ->select(
                'insumos.id',
                'insumos.descricao',
                'insumos.categoria',
                'insumos.tipo_medida',
                'insumos.preco_material',
                'insumos.preco_mao_obra',
                DB::raw('sum('.self::QUANTIDADE_SQL.') as quantidade_total'),
                DB::raw('count(*) as qtd_itens'),
                DB::raw('count(distinct projetos.id) as qtd_projetos')
            )
            ->groupBy('insumos.id')
            ->orderByDesc('quantidade_total')
            ->get();

        // Preço médio que ficou congelado nos orçamentos (material + mão de obra)
        $precosOrcados = DB::table('orcamento_itens')
            ->join('orcamentos', 'orcamentos.id', '=', 'orcamento_itens.orcamento_id')
            ->where('orcamento_itens.empresa_id', $filtro->empresaId)
            ->whereNull('orcamento_itens.deleted_at')
            ->whereNull('orcamentos.deleted_at')
            ->whereNotNull('orcamento_itens.insumo_id')
            ->whereIn('orcamentos.projeto_id', $filtro->projetos()->select('id'))
            ->when($filtro->inicio(), fn ($q, $inicio) => $q->where('orcamento_itens.created_at', '>=', $inicio))
            ->select(
                'orcamento_itens.insumo_id',
                DB::raw('avg(orcamento_itens.preco_material + orcamento_itens.preco_mao_obra) as preco_medio')
            )
            ->groupBy('orcamento_itens.insumo_id')
            ->pluck('preco_medio', 'insumo_id');

        $unidades = ['area' => 'm²', 'volume' => 'm³', 'unidade' => 'un'];

        $linhas = $consumo->map(function ($i) use ($precosOrcados, $unidades) {
            $quantidade = round((float) $i->quantidade_total, 3);
            $material = (float) $i->preco_material;
            $maoObra = (float) $i->preco_mao_obra;
            $precoAtual = $material + $maoObra;

            $medio = $precosOrcados->has($i->id) ? (float) $precosOrcados[$i->id] : null;
            $variacao = ($medio !== null && $medio > 0)
                ? round(($precoAtual - $medio) / $medio * 100, 1)
                : null;

            return [
                $i->descricao,
                $i->categoria ?: '—',
                $unidades[$i->tipo_medida] ?? 'un',
                $quantidade,
                (int) $i->qtd_projetos,
                (int) $i->qtd_itens,
                $material,
                $maoObra,
                round($quantidade * $precoAtual, 2),
                $medio !== null ? round($medio, 2) : null,
                $variacao,
            ];
        })->values();

        return Excel::download(new ConsumoInsumosExport($linhas), 'consumo-de-insumos.xlsx');
    }
}