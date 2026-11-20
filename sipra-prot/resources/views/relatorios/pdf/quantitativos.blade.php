@php
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $unidade = ['area' => 'm²', 'volume' => 'm³', 'unidade' => 'un'];
    $medidas = fn ($i) => match ($i->tipo) {
        'area'   => $num($i->comprimento).' × '.$num($i->largura),
        'volume' => $num($i->comprimento).' × '.$num($i->largura).' × '.$num($i->altura),
        default  => $num($i->qtd_informada),
    };
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

    @forelse ($projetos as $linhas)
        @php
            $p = $linhas->first();
            $revisar = $linhas->filter(fn ($i) => (float) $i->quantidade <= 0)->count();
            $totais = $linhas->filter(fn ($i) => (float) $i->quantidade > 0)
                ->groupBy('insumo')
                ->map(fn ($g) => ['tipo' => $g->first()->tipo, 'quantidade' => $g->sum('quantidade')]);
        @endphp

        @if (! $loop->first)
            <div style="page-break-before: always;"></div>
        @endif

        <h2 style="font-size:14px; margin-top:0;">
            {{ $p->projeto }} <span style="font-weight:normal; color:#666;">— {{ $p->cliente }}</span>
        </h2>

        @if ($revisar > 0)
            <p style="color:#c0392b; font-size:10px; margin:0 0 6px;">
                {{ $revisar }} item(ns) com medida inconsistente ficaram de fora das somas.
            </p>
        @endif

        <table class="dados">
            <thead>
                <tr><th>Ambiente</th><th>Insumo</th><th>Medidas</th><th class="dir">Quantidade</th><th>Observação</th></tr>
            </thead>
            <tbody>
                @foreach ($linhas as $i)
                    <tr>
                        <td>{{ $i->ambiente }}</td>
                        <td>{{ $i->insumo }}</td>
                        <td>{{ $medidas($i) }}</td>
                        <td class="dir">
                            @if ((float) $i->quantidade > 0)
                                {{ number_format((float) $i->quantidade, 2, ',', '.') }} {{ $unidade[$i->tipo] ?? 'un' }}
                            @else
                                <span style="color:#c0392b;">Revisar medida</span>
                            @endif
                        </td>
                        <td>{{ $i->observacao }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h2>Totais por insumo</h2>
        <table class="dados">
            <thead><tr><th>Insumo</th><th class="dir">Quantidade total</th></tr></thead>
            <tbody>
                @forelse ($totais as $insumo => $t)
                    <tr>
                        <td>{{ $insumo }}</td>
                        <td class="dir">{{ number_format($t['quantidade'], 2, ',', '.') }} {{ $unidade[$t['tipo']] ?? 'un' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">Nenhum item válido neste projeto.</td></tr>
                @endforelse
            </tbody>
        </table>
    @empty
        <p>Nenhum item de levantamento neste filtro.</p>
    @endforelse
</body>
</html>