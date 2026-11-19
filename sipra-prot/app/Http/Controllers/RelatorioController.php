<?php

namespace App\Http\Controllers;

use App\Enums\Tipologia;
use App\Services\FiltroRelatorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    public function index(Request $request)
    {
        $filtro = FiltroRelatorio::daRequisicao($request);

        $responsaveis = DB::table('users')
            ->where('empresa_id', $filtro->empresaId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('relatorios.index', $filtro->resumo() + [
            'periodo'       => $filtro->periodo,
            'periodos'      => FiltroRelatorio::PERIODOS,
            'periodoRotulo' => $filtro->rotuloPeriodo(),
            'responsavelId' => $filtro->responsavelId,
            'responsaveis'  => $responsaveis,
            'tipologia'     => $filtro->tipologia,
            'tipologias'    => Tipologia::opcoes(),
            'parametros'    => $filtro->parametros(),
        ]);
    }
}