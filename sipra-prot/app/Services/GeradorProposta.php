<?php

namespace App\Services;

use App\Enums\StatusProposta;
use App\Models\Projeto;
use App\Models\Proposta;
use Illuminate\Support\Facades\DB;

class GeradorProposta
{
    public const CONDICOES_PADRAO = "Pagamento: 50% na aprovação da proposta e 50% na entrega.\nPrazo de execução a combinar após a aprovação.\nOs valores incluem materiais e mão de obra.";

    public function __construct(private GeradorOrcamento $orcamentos)
    {
    }

    /** Congela o orçamento salvo numa proposta em rascunho. */
    public function gerar(Projeto $projeto, int $validadeDias = 15): Proposta
    {
        $orcamento = $projeto->orcamento()->with('itens')->first();

        if (! $orcamento || $orcamento->itens->isEmpty()) {
            throw new \DomainException('Gere e salve o orçamento antes de criar a proposta.');
        }

        $totais = $this->orcamentos->totais($orcamento);

        if ($totais['total'] <= 0) {
            throw new \DomainException('O orçamento está com valor zero. Confira os preços.');
        }

        $perdas = (float) $orcamento->perdas_percentual;
        $bdi = (float) $orcamento->bdi_percentual;

        // Cada item já leva o BDI rateado, então a soma das linhas fecha com o total.
        $itens = [];
        $soma = 0.0;

        foreach ($orcamento->itens as $item) {
            $quantidade = (float) $item->quantidade;
            $custo = round($quantidade * (float) $item->preco_material * (1 + $perdas / 100), 2)
                + round($quantidade * (float) $item->preco_mao_obra, 2);
            $valor = round($custo * (1 + $bdi / 100), 2);

            $itens[] = [
                'descricao' => $item->descricao,
                'unidade' => $item->unidade,
                'quantidade' => $quantidade,
                'valor' => $valor,
            ];
            $soma += $valor;
        }

        // Diferença de centavos por arredondamento vai para a última linha
        $diferenca = round($totais['total'] - $soma, 2);
        if ($diferenca != 0.0) {
            $ultimo = array_key_last($itens);
            $itens[$ultimo]['valor'] = round($itens[$ultimo]['valor'] + $diferenca, 2);
        }

        return DB::transaction(function () use ($projeto, $orcamento, $totais, $itens, $validadeDias) {
            $ano = (int) now()->year;
            $sequencia = ((int) Proposta::withTrashed()->where('ano', $ano)->max('sequencia')) + 1;

            return Proposta::create([
                'projeto_id' => $projeto->id,
                'orcamento_id' => $orcamento->id,
                'ano' => $ano,
                'sequencia' => $sequencia,
                'numero' => sprintf('%04d/%d', $sequencia, $ano),
                'status' => StatusProposta::Rascunho,
                'validade_ate' => now()->addDays($validadeDias)->toDateString(),
                'valor_total' => $totais['total'],
                'itens' => $itens,
                'condicoes' => self::CONDICOES_PADRAO,
            ]);
        });
    }
}