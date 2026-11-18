@csrf

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <flux:input name="nome" label="Nome do projeto" :value="old('nome', $projeto->nome)" required />
    </div>

    <flux:field>
        <flux:label>Cliente</flux:label>
        <select name="cliente_id" required class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
            <option value="">Selecione...</option>
            @foreach ($clientes as $c)
                <option value="{{ $c->id }}" @selected((int) old('cliente_id', $projeto->cliente_id) === $c->id)>{{ $c->nome }}</option>
            @endforeach
        </select>
        <flux:error name="cliente_id" />
    </flux:field>

    <flux:field>
        <flux:label>Responsável</flux:label>
        <select name="responsavel_id" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
            <option value="">Sem responsável</option>
            @foreach ($responsaveis as $u)
                <option value="{{ $u->id }}" @selected((int) old('responsavel_id', $projeto->responsavel_id) === $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
        <flux:error name="responsavel_id" />
    </flux:field>

    <flux:field>
        <flux:label>Status</flux:label>
        <select name="status" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">
            @foreach (\App\Enums\StatusProjeto::cases() as $s)
                <option value="{{ $s->value }}" @selected(old('status', $projeto->status?->value) === $s->value)>{{ $s->rotulo() }}</option>
            @endforeach
        </select>
        <flux:error name="status" />
    </flux:field>

    <flux:input name="prazo" type="date" label="Prazo" :value="old('prazo', $projeto->prazo?->format('Y-m-d'))" />

    <flux:field class="md:col-span-2">
        <flux:label>Briefing</flux:label>
        <textarea name="briefing" rows="4" class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-900">{{ old('briefing', $projeto->briefing) }}</textarea>
        <flux:error name="briefing" />
    </flux:field>
</div>

<div class="mt-8 flex gap-3">
    <flux:button variant="primary" type="submit">Salvar</flux:button>
    <flux:button variant="ghost" :href="route('dashboard')">Cancelar</flux:button>
</div>