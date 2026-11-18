<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $busca = trim((string) $request->input('busca'));
        $tipo = $request->input('tipo');

        $clientes = Cliente::query()
            ->when($busca !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nome', 'like', "%{$busca}%")
                ->orWhere('documento', 'like', "%{$busca}%")
                ->orWhere('cidade', 'like', "%{$busca}%")))
            ->when(in_array($tipo, ['PF', 'PJ'], true), fn ($q) => $q->where('tipo', $tipo))
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'busca', 'tipo'));
    }

    public function create()
    {
        return view('clientes.create', ['cliente' => new Cliente(['tipo' => 'PF'])]);
    }

    public function store(ClienteRequest $request)
    {
        Cliente::create($request->validated());

        return redirect()->route('clientes.index')->with('ok', 'Cliente cadastrado.');
    }

    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(ClienteRequest $request, Cliente $cliente)
    {
        $cliente->update($request->validated());

        return redirect()->route('clientes.index')->with('ok', 'Cliente atualizado.');
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();

        return redirect()->route('clientes.index')->with('ok', 'Cliente excluído.');
    }
}