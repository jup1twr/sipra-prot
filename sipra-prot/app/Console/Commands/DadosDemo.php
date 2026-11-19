<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DadosDemo extends Command
{
    protected $signature = 'sipra:demo {--empresa= : ID da empresa (padrão: a primeira)}';

    protected $description = 'Cria (ou recria) clientes, projetos, levantamentos, orçamentos e propostas de exemplo';

    /** Marca nos e-mails que identifica os clientes de exemplo. */
    private const MARCA = '@demo.sipra.test';

    /** Unidade de cada tipo de medida. Se o seu sistema usa outros valores, ajuste aqui. */
    private const UNIDADES = ['area' => 'm²', 'volume' => 'm³', 'unidade' => 'un'];

    private const CONDICOES = "Pagamento: 50% na aprovação da proposta e 50% na entrega.\nPrazo de execução a combinar após a aprovação.\nOs valores incluem materiais e mão de obra.";

    /** [descrição, categoria, tipo de medida, preço do material, preço da mão de obra] */
    private const INSUMOS = [
        ['Piso porcelanato 80x80', 'Acabamentos', 'area', 89, 42],
        ['Revestimento cerâmico de parede', 'Acabamentos', 'area', 74, 45],
        ['Pintura acrílica de paredes', 'Acabamentos', 'area', 12, 18],
        ['Forro de gesso liso', 'Acabamentos', 'area', 34, 28],
        ['Alvenaria de bloco cerâmico', 'Estrutura', 'area', 58, 40],
        ['Contrapiso de concreto', 'Estrutura', 'volume', 420, 180],
        ['Ponto de tomada ou iluminação', 'Instalações', 'unidade', 38, 55],
        ['Porta de madeira 80 cm', 'Esquadrias', 'unidade', 420, 160],
    ];

    /** [nome, tipo, documento, telefone, cidade, início do e-mail] */
    private const CLIENTES = [
        ['Marcos Alves', 'PF', '123.456.789-00', '(24) 99812-3344', 'Resende', 'marcos.alves'],
        ['Vita Saúde Ltda.', 'PJ', '12.345.678/0001-90', '(24) 3354-1020', 'Resende', 'contato.vita'],
        ['Cond. Aurora', 'PJ', '23.456.789/0001-01', '(24) 3360-4477', 'Itatiaia', 'sindico.aurora'],
        ['Bella Casa Móveis', 'PJ', '34.567.890/0001-12', '(21) 2555-8890', 'Volta Redonda', 'obras.bellacasa'],
        ['Ana Duarte', 'PF', '987.654.321-00', '(21) 99770-1122', 'Rio de Janeiro', 'ana.duarte'],
        ['Nexa Contábil', 'PJ', '45.678.901/0001-23', '(24) 3351-7788', 'Barra Mansa', 'adm.nexa'],
        ['Paulo Ribeiro', 'PF', '456.789.123-44', '(24) 99955-6001', 'Penedo', 'paulo.ribeiro'],
        ['Trigo & Cia', 'PJ', '56.789.012/0001-34', '(24) 3355-2299', 'Resende', 'trigo.padaria'],
    ];

    /** Cada medida: [índice do insumo, comprimento, largura, altura, quantidade] */
    private const AMBIENTES = [
        'Sala de estar' => [[0, 4.2, 5.8, null, null], [2, 20, 2.7, null, null], [3, 4.2, 5.8, null, null], [6, null, null, null, 8], [7, null, null, null, 1]],
        'Cozinha' => [[0, 3.5, 3, null, null], [1, 9.5, 1.2, null, null], [5, 3.5, 3, 0.05, null], [6, null, null, null, 6]],
        'Banheiro social' => [[0, 2.4, 1.8, null, null], [1, 12, 2.4, null, null], [3, 2.4, 1.8, null, null], [7, null, null, null, 1]],
        'Suíte' => [[0, 3.6, 4, null, null], [2, 15.2, 2.7, null, null], [3, 3.6, 4, null, null], [6, null, null, null, 7], [7, null, null, null, 1]],
        'Área externa' => [[0, 6, 3.5, null, null], [4, 12, 1.2, null, null], [6, null, null, null, 4]],
    ];

    /** [nome, índice do cliente, status, ambientes, fator de tamanho, meses atrás, status da proposta] */
    private const PROJETOS = [
        ['Residência Alves – Reforma completa', 0, 'em_levantamento', ['Sala de estar', 'Cozinha', 'Suíte'], 1.0, 0, null],
        ['Clínica Vita Odonto', 1, 'em_levantamento', ['Sala de estar', 'Banheiro social'], 1.4, 0, null],
        ['Edifício Aurora – Hall', 2, 'aguardando_orcamento', ['Sala de estar', 'Área externa'], 1.8, 0, null],
        ['Loja Bella Casa', 3, 'aguardando_orcamento', ['Sala de estar', 'Banheiro social'], 1.6, 0, null],
        ['Cobertura Duarte', 4, 'em_revisao', ['Sala de estar', 'Cozinha', 'Suíte', 'Área externa'], 1.5, 1, 'enviada'],
        ['Escritório Nexa', 5, 'em_revisao', ['Sala de estar', 'Banheiro social'], 1.3, 2, 'recusada'],
        ['Casa de Campo Ribeiro', 6, 'aprovado', ['Sala de estar', 'Cozinha', 'Suíte', 'Banheiro social'], 1.7, 3, 'aprovada'],
        ['Padaria Trigo & Cia', 7, 'aprovado', ['Cozinha', 'Banheiro social', 'Área externa'], 1.2, 4, 'aprovada'],
    ];

    public function handle(): int
    {
        $empresaId = $this->option('empresa') ?: DB::table('empresas')->orderBy('id')->value('id');

        if (! $empresaId || ! DB::table('empresas')->where('id', $empresaId)->exists()) {
            $this->components->error('Empresa não encontrada. Crie uma antes (php artisan sipra:criar-gestor).');

            return self::FAILURE;
        }

        if (! $this->confirm("Recriar os dados de exemplo da empresa #{$empresaId}? Só os dados de exemplo anteriores são apagados.", true)) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($empresaId) {
            $this->limpar($empresaId);
            $this->criar($empresaId);
        });

        return self::SUCCESS;
    }

    private function limpar(int $empresaId): void
    {
        $clientes = DB::table('clientes')->where('empresa_id', $empresaId)->where('email', 'like', '%'.self::MARCA)->pluck('id');
        $projetos = DB::table('projetos')->whereIn('cliente_id', $clientes)->pluck('id');
        $orcamentos = DB::table('orcamentos')->whereIn('projeto_id', $projetos)->pluck('id');
        $ambientes = DB::table('ambientes')->whereIn('projeto_id', $projetos)->pluck('id');

        DB::table('propostas')->whereIn('projeto_id', $projetos)->delete();
        DB::table('orcamento_itens')->whereIn('orcamento_id', $orcamentos)->delete();
        DB::table('orcamentos')->whereIn('id', $orcamentos)->delete();
        DB::table('itens_levantamento')->whereIn('ambiente_id', $ambientes)->delete();
        DB::table('ambientes')->whereIn('id', $ambientes)->delete();

        if (Schema::hasColumn('anexos', 'projeto_id')) {
            DB::table('anexos')->whereIn('projeto_id', $projetos)->delete();
        }

        DB::table('projetos')->whereIn('id', $projetos)->delete();
        DB::table('clientes')->whereIn('id', $clientes)->delete();
    }

    private function criar(int $empresaId): void
    {
        $agora = now();

        // Insumos: reaproveita os que já existem (pela descrição) e cria os que faltam
        $insumos = [];
        foreach (self::INSUMOS as $i => [$descricao, $categoria, $tipo, $material, $maoObra]) {
            $existente = DB::table('insumos')->where('empresa_id', $empresaId)->where('descricao', $descricao)->whereNull('deleted_at')->first();

            $id = $existente?->id ?? DB::table('insumos')->insertGetId([
                'empresa_id' => $empresaId, 'descricao' => $descricao, 'categoria' => $categoria,
                'tipo_medida' => $tipo, 'preco_material' => $material, 'preco_mao_obra' => $maoObra,
                'created_at' => $agora, 'updated_at' => $agora,
            ]);

            $tipoReal = $existente->tipo_medida ?? $tipo;
            $insumos[$i] = [
                'id' => $id, 'descricao' => $descricao, 'tipo' => $tipoReal,
                'un' => self::UNIDADES[$tipoReal] ?? 'un',
                'mat' => (float) ($existente->preco_material ?? $material),
                'mo' => (float) ($existente->preco_mao_obra ?? $maoObra),
            ];
        }

        // Clientes
        $clientes = [];
        foreach (self::CLIENTES as $i => [$nome, $tipo, $documento, $telefone, $cidade, $prefixo]) {
            $clientes[$i] = DB::table('clientes')->insertGetId([
                'empresa_id' => $empresaId, 'nome' => $nome, 'tipo' => $tipo, 'documento' => $documento,
                'telefone' => $telefone, 'email' => $prefixo.self::MARCA, 'cidade' => $cidade,
                'created_at' => $agora, 'updated_at' => $agora,
            ]);
        }

        $responsavel = DB::table('users')->where('empresa_id', $empresaId)->where('ativo', true)->orderBy('id')->value('id');

        // Do mais antigo para o mais novo, para a numeração das propostas seguir as datas
        $lista = self::PROJETOS;
        usort($lista, fn ($a, $b) => $b[5] <=> $a[5]);

        $nProjetos = $nPropostas = 0;

        foreach ($lista as $n => [$nome, $clienteIdx, $status, $ambientes, $fator, $meses, $statusProposta]) {
            $criado = now()->subMonths($meses)->subDays($n)->setTime(10, 0);

            $projetoId = DB::table('projetos')->insertGetId([
                'empresa_id' => $empresaId, 'cliente_id' => $clientes[$clienteIdx], 'responsavel_id' => $responsavel,
                'nome' => $nome, 'status' => $status, 'prazo' => $criado->copy()->addDays(45)->toDateString(),
                'briefing' => 'Projeto de demonstração.', 'created_at' => $criado, 'updated_at' => $criado,
            ]);
            $nProjetos++;

            // Ambientes e itens de levantamento
            $totais = [];
            foreach ($ambientes as $ordem => $nomeAmbiente) {
                $ambienteId = DB::table('ambientes')->insertGetId([
                    'empresa_id' => $empresaId, 'projeto_id' => $projetoId, 'nome' => $nomeAmbiente,
                    'ordem' => $ordem + 1, 'created_at' => $criado, 'updated_at' => $criado,
                ]);

                foreach (self::AMBIENTES[$nomeAmbiente] as [$idx, $c, $l, $h, $q]) {
                    $c = $c ? round($c * $fator, 2) : null;
                    $l = $l ? round($l * $fator, 2) : null;
                    $q = $q ? max(1, (int) round($q * $fator)) : null;

                    $total = match ($insumos[$idx]['tipo']) {
                        'area' => ($c ?? 0) * ($l ?? 0),
                        'volume' => ($c ?? 0) * ($l ?? 0) * ($h ?? 0),
                        default => $q ?? 0,
                    };

                    DB::table('itens_levantamento')->insert([
                        'empresa_id' => $empresaId, 'ambiente_id' => $ambienteId, 'insumo_id' => $insumos[$idx]['id'],
                        'comprimento' => $c, 'largura' => $l, 'altura' => $h, 'quantidade' => $q,
                        'created_at' => $criado, 'updated_at' => $criado,
                    ]);

                    $totais[$idx] = ($totais[$idx] ?? 0) + $total;
                }
            }

            if (! in_array($status, ['em_revisao', 'aprovado'], true)) {
                continue;
            }

            // Orçamento: junta as quantidades por insumo e congela os preços
            $perdas = 5;
            $bdi = 20;
            $orcamentoId = DB::table('orcamentos')->insertGetId([
                'empresa_id' => $empresaId, 'projeto_id' => $projetoId,
                'perdas_percentual' => $perdas, 'bdi_percentual' => $bdi,
                'created_at' => $criado, 'updated_at' => $criado,
            ]);

            $itensProposta = [];
            $soma = 0;
            foreach ($totais as $idx => $quantidade) {
                $i = $insumos[$idx];
                $quantidade = round($quantidade, 3);

                DB::table('orcamento_itens')->insert([
                    'empresa_id' => $empresaId, 'orcamento_id' => $orcamentoId, 'insumo_id' => $i['id'],
                    'descricao' => $i['descricao'], 'unidade' => $i['un'], 'quantidade' => $quantidade,
                    'preco_material' => $i['mat'], 'preco_mao_obra' => $i['mo'],
                    'created_at' => $criado, 'updated_at' => $criado,
                ]);

                $valor = round($quantidade * ($i['mat'] * (1 + $perdas / 100) + $i['mo']) * (1 + $bdi / 100), 2);
                $soma += $valor;
                $itensProposta[] = ['descricao' => $i['descricao'], 'unidade' => $i['un'], 'quantidade' => $quantidade, 'valor' => $valor];
            }

            if (! $statusProposta) {
                continue;
            }

            $ano = (int) $criado->year;
            $sequencia = (int) DB::table('propostas')->where('empresa_id', $empresaId)->where('ano', $ano)->max('sequencia') + 1;

            DB::table('propostas')->insert([
                'empresa_id' => $empresaId, 'projeto_id' => $projetoId, 'orcamento_id' => $orcamentoId,
                'ano' => $ano, 'sequencia' => $sequencia, 'numero' => sprintf('%04d/%d', $sequencia, $ano),
                'status' => $statusProposta, 'validade_ate' => $criado->copy()->addDays(15)->toDateString(),
                'valor_total' => round($soma, 2), 'itens' => json_encode($itensProposta, JSON_UNESCAPED_UNICODE),
                'condicoes' => self::CONDICOES, 'created_at' => $criado, 'updated_at' => $criado,
            ]);
            $nPropostas++;
        }

        $this->components->info(count($clientes)." clientes, {$nProjetos} projetos e {$nPropostas} propostas de exemplo criados.");
    }
}