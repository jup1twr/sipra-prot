<?php

namespace App\Services;

use App\Enums\Tipologia;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonInterface;

class FiltroRelatorio
{
    public const PERIODOS = [
        '3m'   => 'Últimos 3 meses',
        '6m'   => 'Últimos 6 meses',
        '12m'  => 'Últimos 12 meses',
        'ano'  => 'Este ano',
        'todo' => 'Todo o período',
    ];

    public function __construct(
        public readonly int $empresaId,
        public readonly string $periodo,
        public readonly ?int $responsavelId,
        public readonly ?string $tipologia,
    ) {
    }

    public static function daRequisicao(Request $request): self
    {
        $periodo = $request->string('periodo')->toString();

        return new self(
            (int) auth()->user()->empresa_id,
            isset(self::PERIODOS[$periodo]) ? $periodo : '6m',
            $request->integer('responsavel') ?: null,
            Tipologia::tryFrom($request->string('tipologia')->toString())?->value,
        );
    }

    public function inicio(): ?CarbonInterface
    {
        return match ($this->periodo) {
            '3m'    => now()->startOfMonth()->subMonths(2),
            '6m'    => now()->startOfMonth()->subMonths(5),
            '12m'   => now()->startOfMonth()->subMonths(11),
            'ano'   => now()->startOfYear(),
            default => null,
        };
    }

    public function rotuloPeriodo(): string
    {
        return self::PERIODOS[$this->periodo];
    }

    /** Filtros para repassar nos links de exportação */
    public function parametros(): array
    {
        return array_filter([
            'periodo'     => $this->periodo,
            'responsavel' => $this->responsavelId,
            'tipologia'   => $this->tipologia,
        ]);
    }

    /** Texto dos filtros, para o cabeçalho dos PDFs */
    public function descricao(): string
    {
        $partes = ['Período: '.$this->rotuloPeriodo()];

        if ($this->responsavelId) {
            $nome = DB::table('users')
                ->where('id', $this->responsavelId)
                ->where('empresa_id', $this->empresaId)
                ->value('name');

            if ($nome) {
                $partes[] = 'Responsável: '.$nome;
            }
        }

        if ($this->tipologia) {
            $partes[] = 'Tipologia: '.Tipologia::from($this->tipologia)->rotulo();
        }

        return implode(' · ', $partes);
    }

    /** Propostas da empresa (sem excluídas), filtradas por responsável e tipologia, sem o período */
    public function propostasSemPeriodo(): Builder
    {
        return DB::table('propostas')
            ->join('projetos', 'projetos.id', '=', 'propostas.projeto_id')
            ->where('propostas.empresa_id', $this->empresaId)
            ->whereNull('propostas.deleted_at')
            ->when($this->responsavelId, fn ($q) => $q->where('projetos.responsavel_id', $this->responsavelId))
            ->when($this->tipologia, fn ($q) => $q->where('projetos.tipologia', $this->tipologia));
    }

    /** As mesmas propostas, já dentro do período escolhido */
    public function propostas(): Builder
    {
        return $this->propostasSemPeriodo()
            ->when($this->inicio(), fn ($q, $inicio) => $q->where('propostas.created_at', '>=', $inicio));
    }

    public function projetos(): Builder
    {
        return DB::table('projetos')
            ->where('empresa_id', $this->empresaId)
            ->whereNull('deleted_at')
            ->when($this->responsavelId, fn ($q) => $q->where('responsavel_id', $this->responsavelId))
            ->when($this->tipologia, fn ($q) => $q->where('tipologia', $this->tipologia));
    }

    /** Todos os números usados na tela e no PDF geral */
    public function resumo(): array
    {
        $inicio = $this->inicio();

        $quantidade = $this->propostas()->count();
        $valorTotal = (float) $this->propostas()->sum('propostas.valor_total');
        $nAprovadas = $this->propostas()->where('propostas.status', 'like', 'aprov%')->count();
        $nRecusadas = $this->propostas()->where('propostas.status', 'like', 'recus%')->count();
        $decididas = $nAprovadas + $nRecusadas;
        $taxaAprovacao = $decididas > 0 ? round($nAprovadas * 100 / $decididas) : null;

        $projetosAtivos = $this->projetos()->where('status', '!=', 'aprovado')->count();
        $projetosNovos = $this->projetos()
            ->when($inicio, fn ($q) => $q->where('created_at', '>=', $inicio))
            ->count();

        // Valor orçado por mês
        if ($inicio) {
            $inicioGrafico = $inicio->copy();
        } else {
            $primeira = $this->propostasSemPeriodo()->min('propostas.created_at');
            $inicioGrafico = $primeira ? Carbon::parse($primeira)->startOfMonth() : now()->startOfMonth();
            $limite = now()->startOfMonth()->subMonths(23);
            if ($inicioGrafico->lt($limite)) {
                $inicioGrafico = $limite;
            }
        }

        $porMes = $this->propostas()
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

        $porStatus = $this->propostas()
            ->select('propostas.status', DB::raw('count(*) as qtd'), DB::raw('sum(propostas.valor_total) as valor'))
            ->groupBy('propostas.status')
            ->orderByDesc('valor')
            ->get();

        $topClientes = $this->propostas()
            ->join('clientes', 'clientes.id', '=', 'projetos.cliente_id')
            ->select('clientes.nome', DB::raw('count(*) as qtd'), DB::raw('sum(propostas.valor_total) as valor'))
            ->groupBy('clientes.id', 'clientes.nome')
            ->orderByDesc('valor')
            ->limit(5)
            ->get();

        $projetosPorStatus = $this->projetos()
            ->select('status', DB::raw('count(*) as qtd'))
            ->groupBy('status')
            ->pluck('qtd', 'status');

        return compact(
            'quantidade', 'valorTotal', 'nAprovadas', 'decididas', 'taxaAprovacao',
            'projetosAtivos', 'projetosNovos',
            'porStatus', 'serieMeses', 'topClientes', 'projetosPorStatus'
        );
    }
}