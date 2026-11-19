<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Insumo;
use Illuminate\Database\Seeder;

class InsumosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->orderBy('id')->first();

        if (! $empresa || Insumo::withoutGlobalScopes()->where('empresa_id', $empresa->id)->exists()) {
            return; // não duplica se já existir
        }

        $lista = [
            // descrição, categoria, tipo, material, mão de obra
            ['Piso porcelanato 60x60', 'Revestimentos', 'area', 89.90, 45.00],
            ['Contrapiso', 'Revestimentos', 'area', 28.00, 22.00],
            ['Reboco de parede', 'Alvenaria', 'area', 14.00, 26.00],
            ['Alvenaria de bloco cerâmico', 'Alvenaria', 'area', 55.00, 48.00],
            ['Pintura acrílica de parede', 'Pintura', 'area', 12.50, 18.00],
            ['Concreto usinado fck 25', 'Estrutura', 'volume', 420.00, 150.00],
            ['Rodapé 10 cm', 'Acabamentos', 'linear', 18.00, 6.00],
            ['Porta interna completa', 'Esquadrias', 'unidade', 380.00, 120.00],
            ['Janela de alumínio 1,20 x 1,00', 'Esquadrias', 'unidade', 650.00, 90.00],
            ['Ponto elétrico (tomada)', 'Elétrica', 'unidade', 28.00, 45.00],
        ];

        foreach ($lista as [$descricao, $categoria, $tipo, $material, $maoDeObra]) {
            Insumo::forceCreate([
                'empresa_id' => $empresa->id,
                'descricao' => $descricao,
                'categoria' => $categoria,
                'tipo_medida' => $tipo,
                'preco_material' => $material,
                'preco_mao_obra' => $maoDeObra,
            ]);
        }
    }
}