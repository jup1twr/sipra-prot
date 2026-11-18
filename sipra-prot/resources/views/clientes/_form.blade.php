@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <flux:input name="nome" label="Nome ou razão social" :value="old('nome', $cliente->nome)" required />
    </div>

    <flux:field>
        <flux:label>Tipo</flux:label>
        <select name="tipo" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
            <option value="PF" @selected(old('tipo', $cliente->tipo) === 'PF')>Pessoa física</option>
            <option value="PJ" @selected(old('tipo', $cliente->tipo) === 'PJ')>Pessoa jurídica</option>
        </select>
        <flux:error name="tipo" />
    </flux:field>

    <flux:input name="documento" label="CPF ou CNPJ" :value="old('documento', $cliente->documento)" />
    <flux:input name="telefone" label="Telefone" :value="old('telefone', $cliente->telefone)" />
    <flux:input name="email" type="email" label="E-mail" :value="old('email', $cliente->email)" />
    <flux:input name="cidade" label="Cidade" :value="old('cidade', $cliente->cidade)" />
</div>

<div class="mt-8 flex gap-3">
    <flux:button variant="primary" type="submit">Salvar</flux:button>
    <flux:button variant="ghost" :href="route('clientes.index')">Cancelar</flux:button>
</div>