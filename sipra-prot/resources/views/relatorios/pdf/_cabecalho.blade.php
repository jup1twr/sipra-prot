<table style="width:100%; border-bottom:2px solid #0e304e; margin-bottom:14px;">
    <tr>
        <td style="width:50%; padding-bottom:8px;">
            @if (file_exists(public_path('img/sipra-logo-completo-pdf-branco.png')))
                <img src="{{ public_path('img/sipra-logo-completo-pdf-branco.png') }}" style="height:70px;">
            @else
                <b style="font-size:18px; color:#0e304e;">SIPRA</b>
            @endif
        </td>
        <td style="text-align:right; font-size:10px; color:#555; padding-bottom:8px;">
            <div style="font-size:15px; font-weight:bold; color:#0e304e;">{{ $titulo }}</div>
            {{ $filtroDescricao }}<br>
            Gerado em {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()->name }}
        </td>
    </tr>
</table>