@php
    $moeda = fn ($v) => 'R$ '.number_format((float) $v, 2, ',', '.');
    $qtd = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',');
    $cliente = $proposta->projeto?->cliente;
    $logoArquivo = public_path('img/sipra-logo-pdf.jpg');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Proposta {{ $proposta->numero }}</title>
    <style>
        @page { margin: 34px 38px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; }
        h1 { margin: 0; font-size: 19px; color: #0e304e; }
        .rotulo { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
        .valor { font-size: 12px; font-weight: bold; color: #0e304e; }
        .faixa { border-top: 3px solid #078c61; margin: 14px 0 16px; }
        .caixa { border: 1px solid #d4d4d8; padding: 9px 12px; }
        .itens th { background: #0e304e; color: #ffffff; text-align: left; padding: 7px 8px; font-size: 9px; text-transform: uppercase; }
        .itens td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .dir { text-align: right; }
        .total td { background: #e8f5ef; color: #0e304e; font-size: 14px; font-weight: bold; padding: 11px 8px; }
        .condicoes { margin-top: 18px; line-height: 1.6; }
        .assinaturas { margin-top: 56px; }
        .linha { border-top: 1px solid #6b7280; padding-top: 5px; text-align: center; font-size: 9px; color: #4b5563; }
        .rodape { margin-top: 26px; text-align: center; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td style="width: 55%; vertical-align: middle;">
                @if (file_exists($logoArquivo))
                    <img src="{{ str_replace('\\', '/', $logoArquivo) }}" alt="SIPRA" style="height: 60px;">
                @else
                    <h1>SIPRA</h1>
                @endif
            </td>
            <td style="width: 45%; text-align: right; vertical-align: middle;">
                <div class="rotulo">Proposta comercial</div>
                <h1>Nº {{ $proposta->numero }}</h1>
            </td>
        </tr>
    </table>

    <div class="faixa"></div>

    <table>
        <tr>
            <td style="width: 50%; padding-right: 8px; vertical-align: top;">
                <div class="caixa">
                    <div class="rotulo">Cliente</div>
                    <div class="valor">{{ $cliente?->nome ?? '—' }}</div>
                    @if ($cliente?->documento) <div>{{ $cliente->tipo === 'PJ' ? 'CNPJ' : 'CPF' }}: {{ $cliente->documento }}</div> @endif
                    @if ($cliente?->telefone) <div>Tel.: {{ $cliente->telefone }}</div> @endif
                    @if ($cliente?->email) <div>{{ $cliente->email }}</div> @endif
                    @if ($cliente?->cidade) <div>{{ $cliente->cidade }}</div> @endif
                </div>
            </td>
            <td style="width: 50%; padding-left: 8px; vertical-align: top;">
                <div class="caixa">
                    <div class="rotulo">Emitente</div>
                    <div class="valor">{{ $empresa?->nome ?? 'SIPRA' }}</div>
                    @if ($empresa?->documento) <div>CNPJ: {{ $empresa->documento }}</div> @endif
                    <div style="margin-top: 6px;"><span class="rotulo">Emissão:</span> {{ $proposta->created_at->format('d/m/Y') }}</div>
                    <div><span class="rotulo">Válida até:</span> <strong>{{ $proposta->validade_ate->format('d/m/Y') }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    <div style="margin: 16px 0 8px;">
        <div class="rotulo">Projeto</div>
        <div class="valor">{{ $proposta->projeto?->nome ?? '—' }}</div>
    </div>

    <table class="itens">
        <thead>
            <tr>
                <th style="width: 6%;">Item</th>
                <th>Descrição</th>
                <th class="dir" style="width: 18%;">Quantidade</th>
                <th class="dir" style="width: 22%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($proposta->itens as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item['descricao'] }}</td>
                    <td class="dir">{{ $qtd($item['quantidade']) }} {{ $item['unidade'] }}</td>
                    <td class="dir">{{ $moeda($item['valor']) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="3" class="dir">TOTAL</td>
                <td class="dir">{{ $moeda($proposta->valor_total) }}</td>
            </tr>
        </tfoot>
    </table>

    @if ($proposta->condicoes)
        <div class="condicoes">
            <div class="rotulo">Condições</div>
            {!! nl2br(e($proposta->condicoes)) !!}
        </div>
    @endif

    <table class="assinaturas">
        <tr>
            <td style="width: 45%;"><div class="linha">{{ $empresa?->nome ?? 'Emitente' }}</div></td>
            <td style="width: 10%;"></td>
            <td style="width: 45%;"><div class="linha">{{ $cliente?->nome ?? 'Cliente' }} (de acordo)</div></td>
        </tr>
    </table>

    <div class="rodape">Proposta {{ $proposta->numero }} · gerada pelo SIPRA</div>
</body>
</html>