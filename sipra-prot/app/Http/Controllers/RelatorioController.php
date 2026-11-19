<?php

namespace App\Http\Controllers;

use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\Tipologia;

class RelatorioController extends Controller
{
    private const PERIODOS = [
        '3m'   => 'Últimos 3 meses',
        '6m'   => 'Últimos 6 meses',
        '12m'  => 'Últimos 12 meses',
        'ano'  => 'Este ano',
        'todo' => 'Todo o período',
    ];

    public function index(Request $request)
    {
        $empresaId = auth()->user()->empresa_id;

        // Filtros (valores inválidos voltam ao padrão)
        $periodo = $request->string('periodo')->toString();
        $periodo = isset(self::PERIODOS[$periodo]) ? $periodo : '6m';
        $responsavelId = $request->integer('responsavel') ?: null;
        $tipologia = Tipologia::tryFrom($request->string('tipologia')->toString())?->value;

        $inicio = match ($periodo) {
            '3m'    => now()->startOfMonth()->subMonths(2),
            '6m'    => now()->startOfMonth()->subMonths(5),
            '12m'   => now()->startOfMonth()->subMonths(11),
            'ano'   => now()->startOfYear(),
            default => null,
        };

        // Propostas da empresa (sem excluídas), com o filtro de responsável
       $base = fn () => DB::table('propostas')
            ->join('projetos', 'projetos.id', '=', 'propostas.projeto_id')
            ->where('propostas.empresa_id', $empresaId)
            ->whereNull('propostas.deleted_at')
            ->when($responsavelId, fn ($q) => $q->where('projetos.responsavel_id', $responsavelId))
            ->when($tipologia, fn ($q) => $q->where('projetos.tipologia', $tipologia));

        // Mesmas propostas, já dentro do período escolhido
        $propostas = fn () => $base()
            ->when($inicio, fn ($q) => $q->where('propostas.created_at', '>=', $inicio));

        // Indicadores
        $quantidade = $propostas()->count();
        $valorTotal = (float) $propostas()->sum('propostas.valor_total');
        $nAprovadas = $propostas()->where('propostas.status', 'like', 'aprov%')->count();
        $nRecusadas = $propostas()->where('propostas.status', 'like', 'recus%')->count();
        $decididas = $nAprovadas + $nRecusadas;
        $taxaAprovacao = $decididas > 0 ? round($nAprovadas * 100 / $decididas) : null;

        $projetos = fn () => DB::table('projetos')
            ->where('empresa_id', $empresaId)
            ->whereNull('deleted_at')
            ->when($responsavelId, fn ($q) => $q->where('responsavel_id', $responsavelId))
            ->when($tipologia, fn ($q) => $q->where('tipologia', $tipologia));

        $projetosAtivos = $projetos()->where('status', '!=', 'aprovado')->count();
        $projetosNovos = $projetos()
            ->when($inicio, fn ($q) => $q->where('created_at', '>=', $inicio))
            ->count();

        // Valor orçado por mês
        if ($inicio) {
            $inicioGrafico = $inicio->copy();
        } else {
            $primeira = $base()->min('propostas.created_at');
            $inicioGrafico = $primeira ? \Carbon\Carbon::parse($primeira)->startOfMonth() : now()->startOfMonth();
            $limite = now()->startOfMonth()->subMonths(23);
            if ($inicioGrafico->lt($limite)) {
                $inicioGrafico = $limite;
            }
        }

        $porMes = $propostas()
            ->where('propostas.created_at', '>=', $inicioGrafico)
            ->select(DB::raw("DATE_FORMAT(propostas.created_at, '%Y-%m') as mes"), DB::raw('sum(propostas.valor_total) as valor'))
            ->groupBy('mes')
            ->pluck('valor', 'mes');

        $serieMeses = collect(CarbonPeriod::create($inicioGrafico, '1 month', now()->startOfMonth()))
            ->map(fn ($m) => [
                'rotulo' => $m->translatedFormat('M/y'),
                'valor'  => (float) ($porMes[$m->format('Y-m')] ?? 0),
                'atual'  => $m->isSameMonth(now()),
            ]);

        // Propostas por status
        $porStatus = $propostas()
            ->select('propostas.status', DB::raw('count(*) as qtd'), DB::raw('sum(propostas.valor_total) as valor'))
            ->groupBy('propostas.status')
            ->orderByDesc('valor')
            ->get();

        // Maiores clientes
        $topClientes = $propostas()
            ->join('clientes', 'clientes.id', '=', 'projetos.cliente_id')
            ->select('clientes.nome', DB::raw('count(*) as qtd'), DB::raw('sum(propostas.valor_total) as valor'))
            ->groupBy('clientes.id', 'clientes.nome')
            ->orderByDesc('valor')
            ->limit(5)
            ->get();

        // Projetos por status (situação atual, só filtra por responsável)
        $projetosPorStatus = $projetos()
            ->select('status', DB::raw('count(*) as qtd'))
            ->groupBy('status')
            ->pluck('qtd', 'status');

        $responsaveis = DB::table('users')
            ->where('empresa_id', $empresaId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $periodos = self::PERIODOS;
        $periodoRotulo = self::PERIODOS[$periodo];
        $tipologias = Tipologia::opcoes();

        return view('relatorios.index', compact(
            'periodo', 'periodos', 'periodoRotulo', 'responsavelId', 'responsaveis',
            'tipologia', 'tipologias',
            'quantidade', 'valorTotal', 'nAprovadas', 'decididas', 'taxaAprovacao',
            'projetosAtivos', 'projetosNovos',
            'porStatus', 'serieMeses', 'topClientes', 'projetosPorStatus'
        ));
    }
}