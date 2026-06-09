<?php

namespace App\Exports;

use App\Models\RedemptionLog;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class RedemptionReportExport implements FromView, WithTitle, WithStyles, ShouldAutoSize
{
    protected $redemptions;
    protected $filters;

    public function __construct($redemptions, array $filters = [])
    {
        $this->redemptions = $redemptions;
        $this->filters = $filters;
    }

    public function view(): View
    {
        return view('exports.redemption-report', [
            'redemptions' => $this->redemptions,
            'filters' => $this->filters,
            'exportDate' => now()->format('Y-m-d H:i:s'),
            'totalRedemptions' => $this->redemptions->count(),
            'totalPax' => $this->redemptions->sum('pax_used'),
        ]);
    }

    public function title(): string
    {
        return 'Redemption Report';
    }

    public function styles(Worksheet $sheet)
    {
        // Title section styling (rows 1-4)
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1F4E78'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Header row styling (row 6)
        $sheet->getStyle('A6:K6')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E75B6'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->getRowDimension(6)->setRowHeight(25);

        // Apply borders to all data cells
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 6) {
            $sheet->getStyle("A6:K{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            // Alternate row colors for data
            for ($i = 7; $i <= $lastRow; $i++) {
                if ($i % 2 == 0) {
                    $sheet->getStyle("A{$i}:K{$i}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E9F2F9'],
                        ],
                    ]);
                }
            }
        }

        return [];
    }
}
