<?php

namespace App\Http\Controllers;

use App\Enums\Papel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /** Só usuários da empresa de quem está logado. */
    private function daEmpresa()
    {
        return User::query()->where('empresa_id', auth()->user()->empresa_id);
    }

    public function index()
    {
        return view('usuarios.index', ['usuarios' => $this->daEmpresa()->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('usuarios.create', [
            'usuario' => (new User)->forceFill(['papel' => 'orcamentista', 'ativo' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'papel' => ['required', Rule::enum(Papel::class)],
            'password' => ['required', Password::min(8)],
        ], [
            'email.unique' => 'Já existe um usuário com este e-mail.',
        ]);

        (new User)->forceFill([
            'name' => $dados['name'],
            'email' => $dados['email'],
            'password' => Hash::make($dados['password']),
            'papel' => $dados['papel'],
            'ativo' => true,
            'empresa_id' => auth()->user()->empresa_id,
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('usuarios.index')->with('ok', 'Usuário criado.');
    }

    public function edit(string $usuario)
    {
        return view('usuarios.edit', ['usuario' => $this->daEmpresa()->findOrFail($usuario)]);
    }

    public function update(Request $request, string $usuario)
    {
        $usuario = $this->daEmpresa()->findOrFail($usuario);

        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'papel' => ['required', Rule::enum(Papel::class)],
            'password' => ['nullable', Password::min(8)],
        ], [
            'email.unique' => 'Já existe um usuário com este e-mail.',
        ]);

        $ativo = $request->boolean('ativo');

        // Segurança: ninguém tira o próprio acesso de gestor nem desativa a própria conta.
        // Assim, sempre sobra pelo menos um gestor ativo.
        if ($usuario->is(auth()->user()) && ($dados['papel'] !== 'gestor' || ! $ativo)) {
            return back()->withInput()->with('erro', 'Você não pode rebaixar nem desativar a própria conta.');
        }

        $usuario->forceFill([
            'name' => $dados['name'],
            'email' => $dados['email'],
            'papel' => $dados['papel'],
            'ativo' => $ativo,
        ]);

        if (! empty($dados['password'])) {
            $usuario->password = Hash::make($dados['password']);
        }

        $usuario->save();

        return redirect()->route('usuarios.index')->with('ok', 'Usuário atualizado.');
    }
}