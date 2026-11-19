<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProdutividadeExport implements FromCollection, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles, WithTitle
{
    public function __construct(private Collection $linhas)
    {
    }

    public function collection(): Collection
    {
        return $this->linhas;
    }

    public function headings(): array
    {
        return [
            'Responsável', 'Projetos', 'Em levantamento', 'Aguardando orçamento',
            'Em revisão', 'Aprovados', 'Prazos vencidos',
            'Orçamentos emitidos', 'Orçamentos aprovados', 'Valor orçado (R$)',
        ];
    }

    public function columnFormats(): array
    {
        return ['J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Produtividade';
    }
}