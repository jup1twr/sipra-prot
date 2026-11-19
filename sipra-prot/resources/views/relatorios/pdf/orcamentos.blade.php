@php
    $moeda = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
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

    <table class="dados">
        <thead>
            <tr>
                <th>Nº</th><th>Projeto</th><th>Cliente</th><th>Emissão</th><th>Validade</th><th>Status</th><th class="dir">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($linhas as $l)
                <tr>
                    <td>{{ $l->numero }}</td>
                    <td>{{ $l->projeto }}</td>
                    <td>{{ $l->cliente }}</td>
                    <td>{{ \Carbon\Carbon::parse($l->created_at)->format('d/m/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($l->validade_ate)->format('d/m/Y') }}</td>
                    <td>{{ $rotuloProposta[$l->status] ?? \Illuminate\Support\Str::headline($l->status) }}</td>
                    <td class="dir">{{ $moeda($l->valor_total) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">Nenhuma proposta neste filtro.</td></tr>
            @endforelse

            @if ($linhas->isNotEmpty())
                <tr class="total">
                    <td colspan="6">Total ({{ $linhas->count() }} proposta(s))</td>
                    <td class="dir">{{ $moeda($linhas->sum('valor_total')) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>
</html>