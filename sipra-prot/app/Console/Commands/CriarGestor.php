<?php

namespace App\Console\Commands;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CriarGestor extends Command
{
    protected $signature = 'sipra:criar-gestor';

    protected $description = 'Cria uma empresa nova e o primeiro usuário gestor dela';

    public function handle(): int
    {
        $empresa = trim((string) $this->ask('Nome da empresa'));
        $nome = trim((string) $this->ask('Nome do gestor'));
        $email = strtolower(trim((string) $this->ask('E-mail do gestor')));
        $senha = (string) $this->secret('Senha (mínimo 8 caracteres)');

        if ($empresa === '' || $nome === '') {
            $this->components->error('Informe o nome da empresa e o nome do gestor.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('E-mail inválido.');

            return self::FAILURE;
        }

        if (strlen($senha) < 8) {
            $this->components->error('A senha precisa ter pelo menos 8 caracteres.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->components->error('Já existe um usuário com este e-mail.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($empresa, $nome, $email, $senha) {
            $nova = Empresa::create(['nome' => $empresa]);

            (new User)->forceFill([
                'name' => $nome,
                'email' => $email,
                'password' => Hash::make($senha),
                'papel' => 'gestor',
                'ativo' => true,
                'empresa_id' => $nova->id,
                'email_verified_at' => now(),
            ])->save();
        });

        $this->components->info("Empresa \"{$empresa}\" criada, com {$nome} como gestor.");

        return self::SUCCESS;
    }
}