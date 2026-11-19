@php
    $moeda = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $statusProjeto = [
        'em_levantamento' => 'Em Levantamento',
        'aguardando_orcamento' => 'Aguardando Orçamento',
        'em_revisao' => 'Em Revisão',
        'aprovado' => 'Aprovado',
    ];
    $rotuloProposta = ['rascunho' => 'Rascunho', 'enviada' => 'Enviada', 'aprovada' => 'Aprovada', 'recusada' => 'Recusada'];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    @include('relatorios.pdf._estilo')
</head>
<body>
    @include('relatorios.pdf._cabecalho')

    <table style="width:100%; border-collapse:separate; border-spacing:6px;">
        <tr>
            <td class="cartao"><div class="rotulo">Projetos ativos</div><div class="valor">{{ $projetosAtivos }}</div><div class="nota">+{{ $projetosNovos }} criados no período</div></td>
            <td class="cartao"><div class="rotulo">Orçamentos emitidos</div><div class="valor">{{ $quantidade }}</div><div class="nota">{{ $nAprovadas }} aprovados · {{ $decididas - $nAprovadas }} recusados</div></td>
            <td class="cartao"><div class="rotulo">Total orçado</div><div class="valor">{{ $moeda($valorTotal) }}</div></td>
            <td class="cartao"><div class="rotulo">Taxa de aprovação</div><div class="valor">{{ $taxaAprovacao === null ? '—' : $taxaAprovacao.' %' }}</div><div class="nota">{{ $nAprovadas }} de {{ $decididas }} decididos</div></td>
        </tr>
    </table>

    <h2>Valor orçado por mês</h2>
    <table class="dados">
        <thead><tr><th>Mês</th><th class="dir">Valor orçado</th></tr></thead>
        <tbody>
            @foreach ($serieMeses as $mes)
                <tr><td>{{ $mes['rotulo'] }}</td><td class="dir">{{ $moeda($mes['valor']) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Projetos por status</h2>
    <table class="dados">
        <thead><tr><th>Status</th><th class="dir">Projetos</th></tr></thead>
        <tbody>
            @foreach ($statusProjeto as $chave => $rotulo)
                <tr><td>{{ $rotulo }}</td><td class="dir">{{ (int) ($projetosPorStatus[$chave] ?? 0) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Propostas por status</h2>
    <table class="dados">
        <thead><tr><th>Status</th><th class="dir">Qtd.</th><th class="dir">Valor</th></tr></thead>
        <tbody>
            @forelse ($porStatus as $linha)
                <tr>
                    <td>{{ $rotuloProposta[$linha->status] ?? \Illuminate\Support\Str::headline($linha->status) }}</td>
                    <td class="dir">{{ $linha->qtd }}</td>
                    <td class="dir">{{ $moeda($linha->valor) }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Nenhuma proposta neste filtro.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Maiores clientes</h2>
    <table class="dados">
        <thead><tr><th>Cliente</th><th class="dir">Propostas</th><th class="dir">Valor</th></tr></thead>
        <tbody>
            @forelse ($topClientes as $linha)
                <tr><td>{{ $linha->nome }}</td><td class="dir">{{ $linha->qtd }}</td><td class="dir">{{ $moeda($linha->valor) }}</td></tr>
            @empty
                <tr><td colspan="3">Sem dados neste filtro.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>