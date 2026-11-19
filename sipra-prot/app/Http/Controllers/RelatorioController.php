<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RelatorioController extends Controller
{
    public function index()
    {
        $empresaId = auth()->user()->empresa_id;

        // Propostas da empresa logada, sem as excluídas
        $propostas = fn () => DB::table('propostas')
            ->where('empresa_id', $empresaId)
            ->whereNull('deleted_at');

        $quantidade = $propostas()->count();
        $valorTotal = (float) $propostas()->sum('valor_total');
        $valorAprovado = (float) $propostas()->where('status', 'like', 'aprov%')->sum('valor_total');
        $nAprovadas = $propostas()->where('status', 'like', 'aprov%')->count();
        $nRecusadas = $propostas()->where('status', 'like', 'recus%')->count();
        $decididas = $nAprovadas + $nRecusadas;

        $taxaAprovacao = $decididas > 0 ? round($nAprovadas * 100 / $decididas) : null;
        $ticketMedio = $quantidade > 0 ? $valorTotal / $quantidade : 0;

        $porStatus = $propostas()
            ->select('status', DB::raw('count(*) as qtd'), DB::raw('sum(valor_total) as valor'))
            ->groupBy('status')
            ->orderByDesc('valor')
            ->get();

        // Valor orçado nos últimos 6 meses
        $meses = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        $porMes = $propostas()
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as mes"), DB::raw('sum(valor_total) as valor'))
            ->groupBy('mes')
            ->pluck('valor', 'mes');

        $serieMeses = $meses->map(fn ($m) => [
            'rotulo' => $m->translatedFormat('M/y'),
            'valor' => (float) ($porMes[$m->format('Y-m')] ?? 0),
        ]);

        $topClientes = DB::table('propostas')
            ->join('projetos', 'projetos.id', '=', 'propostas.projeto_id')
            ->join('clientes', 'clientes.id', '=', 'projetos.cliente_id')
            ->where('propostas.empresa_id', $empresaId)
            ->whereNull('propostas.deleted_at')
            ->select('clientes.nome', DB::raw('count(*) as qtd'), DB::raw('sum(propostas.valor_total) as valor'))
            ->groupBy('clientes.id', 'clientes.nome')
            ->orderByDesc('valor')
            ->limit(5)
            ->get();

        $projetosPorStatus = Schema::hasColumn('projetos', 'status')
            ? DB::table('projetos')
                ->where('empresa_id', $empresaId)
                ->when(Schema::hasColumn('projetos', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->select('status', DB::raw('count(*) as qtd'))
                ->groupBy('status')
                ->get()
            : collect();

        return view('relatorios.index', compact(
            'quantidade', 'valorTotal', 'valorAprovado', 'taxaAprovacao', 'ticketMedio',
            'porStatus', 'serieMeses', 'topClientes', 'projetosPorStatus'
        ));
    }
}