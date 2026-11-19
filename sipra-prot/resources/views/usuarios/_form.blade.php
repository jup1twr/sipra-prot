@csrf

@if (session('erro'))
    <div class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-300">
        {{ session('erro') }}
    </div>
@endif

<div class="grid gap-5 md:grid-cols-2">
    <flux:input name="name" label="Nome" :value="old('name', $usuario->name)" required />
    <flux:input name="email" type="email" label="E-mail" :value="old('email', $usuario->email)" required />

    <flux:field>
        <flux:label>Papel</flux:label>
        <select name="papel" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
            @foreach (\App\Enums\Papel::cases() as $papel)
                <option value="{{ $papel->value }}" @selected(old('papel', $usuario->papel) === $papel->value)>{{ $papel->rotulo() }}</option>
            @endforeach
        </select>
        <flux:error name="papel" />
    </flux:field>

    <flux:input
        name="password"
        type="password"
        :label="$usuario->exists ? 'Nova senha (deixe em branco para manter)' : 'Senha inicial'"
        :required="! $usuario->exists"
    />

    @if ($usuario->exists)
        <label class="flex items-center gap-2 text-sm md:col-span-2">
            <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $usuario->ativo))>
            Usuário ativo (desmarque para bloquear o acesso)
        </label>
    @endif
</div>

<div class="mt-8 flex gap-3">
    <flux:button variant="primary" type="submit">Salvar</flux:button>
    <flux:button variant="ghost" :href="route('usuarios.index')">Cancelar</flux:button>
</div>