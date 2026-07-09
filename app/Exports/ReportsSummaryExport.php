<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportsSummaryExport implements FromView, WithTitle, WithStyles, ShouldAutoSize
{
    protected object $overview;
    protected $redemptionsByFacility;
    protected $redemptionsByOutlet;
    protected $dailyTrend;
    protected $redemptionDetails;
    protected array $filters;
    protected string $periodLabel;
    protected int $detailHeaderRow;

    public function __construct(
        object $overview,
        $redemptionsByFacility,
        $redemptionsByOutlet,
        $dailyTrend,
        $redemptionDetails,
        array $filters,
        string $periodLabel,
    ) {
        $this->overview = $overview;
        $this->redemptionsByFacility = $redemptionsByFacility;
        $this->redemptionsByOutlet = $redemptionsByOutlet;
        $this->dailyTrend = $dailyTrend;
        $this->redemptionDetails = $redemptionDetails;
        $this->filters = $filters;
        $this->periodLabel = $periodLabel;

        $facilityRows = max(1, $redemptionsByFacility->count());
        $outletRows = max(1, $redemptionsByOutlet->count());
        $trendRows = max(1, $dailyTrend->count());

        $this->detailHeaderRow = 7 + $facilityRows + 3 + $outletRows + 3 + $trendRows + 3;
    }

    public function view(): View
    {
        return view('exports.reports-summary', [
            'overview' => $this->overview,
            'redemptionsByFacility' => $this->redemptionsByFacility,
            'redemptionsByOutlet' => $this->redemptionsByOutlet,
            'dailyTrend' => $this->dailyTrend,
            'redemptionDetails' => $this->redemptionDetails,
            'filters' => $this->filters,
            'periodLabel' => $this->periodLabel,
            'exportDate' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function title(): string
    {
        return 'Redemption Report';
    }

    public function styles(Worksheet $sheet)
    {
        $lastCol = 'K';

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '1F4E78']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['size' => 11, 'color' => ['rgb' => '444444']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells("A4:{$lastCol}4");
        $sheet->getStyle('A4')->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $this->styleSectionHeader($sheet, 6, $lastCol, 'EXECUTIVE SUMMARY');

        $sheet->getStyle('A7:D7')->applyFromArray($this->tableHeaderStyle('2E75B6'));
        $sheet->getStyle('A8:D8')->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $facilityHeaderRow = 10;
        $this->styleSectionHeader($sheet, $facilityHeaderRow, $lastCol, 'REDEMPTIONS BY FACILITY');
        $this->styleTableHeader($sheet, $facilityHeaderRow + 1, 'A', 'C');

        $facilityDataStart = $facilityHeaderRow + 2;
        $facilityDataEnd = $facilityDataStart + max(0, $this->redemptionsByFacility->count() - 1);
        if ($this->redemptionsByFacility->count() > 0) {
            $this->styleDataRows($sheet, $facilityDataStart, $facilityDataEnd, 'A', 'C');
        }

        $outletHeaderRow = $facilityDataEnd + 2;
        $this->styleSectionHeader($sheet, $outletHeaderRow, $lastCol, 'REDEMPTIONS BY OUTLET');
        $this->styleTableHeader($sheet, $outletHeaderRow + 1, 'A', 'D');

        $outletDataStart = $outletHeaderRow + 2;
        $outletDataEnd = $outletDataStart + max(0, $this->redemptionsByOutlet->count() - 1);
        if ($this->redemptionsByOutlet->count() > 0) {
            $this->styleDataRows($sheet, $outletDataStart, $outletDataEnd, 'A', 'D');
        }

        $trendHeaderRow = $outletDataEnd + 2;
        $this->styleSectionHeader($sheet, $trendHeaderRow, $lastCol, 'DAILY REDEMPTION TREND');
        $this->styleTableHeader($sheet, $trendHeaderRow + 1, 'A', 'C');

        $trendDataStart = $trendHeaderRow + 2;
        $trendDataEnd = $trendDataStart + max(0, $this->dailyTrend->count() - 1);
        if ($this->dailyTrend->count() > 0) {
            $this->styleDataRows($sheet, $trendDataStart, $trendDataEnd, 'A', 'C');
        }

        $detailHeaderRow = $trendDataEnd + 2;
        $this->styleSectionHeader($sheet, $detailHeaderRow, $lastCol, 'DETAILED REDEMPTION LOG');
        $this->styleTableHeader($sheet, $detailHeaderRow + 1, 'A', $lastCol);

        $detailDataStart = $detailHeaderRow + 2;
        $detailDataEnd = $detailDataStart + max(0, $this->redemptionDetails->count() - 1);
        if ($this->redemptionDetails->count() > 0) {
            $this->styleDataRows($sheet, $detailDataStart, $detailDataEnd, 'A', $lastCol);
        }

        $sheet->getRowDimension(1)->setRowHeight(30);

        return [];
    }

    protected function styleSectionHeader(Worksheet $sheet, int $row, string $lastCol, string $title): void
    {
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F4E78']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D6E4F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
    }

    protected function styleTableHeader(Worksheet $sheet, int $row, string $startCol, string $endCol): void
    {
        $sheet->getStyle("{$startCol}{$row}:{$endCol}{$row}")->applyFromArray($this->tableHeaderStyle('2E75B6'));
        $sheet->getRowDimension($row)->setRowHeight(25);
    }

    protected function styleDataRows(Worksheet $sheet, int $startRow, int $endRow, string $startCol, string $endCol): void
    {
        $sheet->getStyle("{$startCol}{$startRow}:{$endCol}{$endRow}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);

        for ($i = $startRow; $i <= $endRow; $i++) {
            if (($i - $startRow) % 2 === 1) {
                $sheet->getStyle("{$startCol}{$i}:{$endCol}{$i}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9F2F9']],
                ]);
            }
        }
    }

    protected function tableHeaderStyle(string $color): array
    {
        return [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
    }
}
