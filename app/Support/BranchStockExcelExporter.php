<?php

namespace App\Support;

use App\Models\PosBranch;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BranchStockExcelExporter
{
    public function __construct(private readonly PosBranch $branch) {}

    public function downloadStockLeft(): StreamedResponse
    {
        $printedAt = PhilippineTime::now();
        $filename = 'branch-'.$this->branch->code.'-stock-left-'.$printedAt->format('Y-m-d').'.xlsx';

        return $this->download($this->buildStockLeft($printedAt->format('m/d/Y H:i:s')), $filename);
    }

    public function downloadTransfers(): StreamedResponse
    {
        $printedAt = PhilippineTime::now();
        $filename = 'branch-'.$this->branch->code.'-transfers-'.$printedAt->format('Y-m-d').'.xlsx';

        return $this->download($this->buildTransfers($printedAt->format('m/d/Y H:i:s')), $filename);
    }

    public function buildStockLeft(string $printedAtLabel): Spreadsheet
    {
        $report = new BranchStockReport($this->branch);
        $rows = $report->stockLeftRows();
        $totals = $report->stockLeftTotals();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Stock left');

        $sheet->setCellValue('A1', 'DICNHS-TEMPUCO');
        $sheet->setCellValue('A2', 'BRANCH STOCK LEFT — '.$this->branch->name);
        $sheet->setCellValue('A3', __('Branch :number · :code', [
            'number' => $this->branch->branch_number,
            'code' => $this->branch->code,
        ]));
        $sheet->setCellValue('A4', $printedAtLabel);

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['SKU', 'PRODUCT', 'QTY LEFT', 'EXPIRES', 'UNIT PRICE', 'VALUE'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 6], $header);
        }

        $sheet->getStyle('A6:F6')->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '5B9BD5']],
            ],
        ]);

        $rowNumber = 7;
        $valueTotal = 0.0;

        foreach ($rows as $row) {
            $item = $row->inventoryItem;
            $price = (float) ($item?->unit_price ?? 0);
            $value = round($row->quantity * $price, 2);
            $valueTotal += $value;

            $sheet->setCellValue("A{$rowNumber}", (string) ($item?->sku ?? ''));
            $sheet->setCellValue("B{$rowNumber}", (string) ($item?->name ?? __('Unknown product')));
            $sheet->setCellValue("C{$rowNumber}", $row->quantity);
            $sheet->setCellValue("D{$rowNumber}", $row->expiration_date?->format('Y-m-d') ?? '');
            $sheet->setCellValue("E{$rowNumber}", $price);
            $sheet->setCellValue("F{$rowNumber}", $value);
            $rowNumber++;
        }

        $sheet->setCellValue("B{$rowNumber}", __('Total'));
        $sheet->setCellValue("C{$rowNumber}", $totals['units']);
        $sheet->setCellValue("F{$rowNumber}", round($valueTotal, 2));
        $sheet->getStyle("A{$rowNumber}:F{$rowNumber}")->getFont()->setBold(true);

        $last = max(7, $rowNumber);
        $sheet->getStyle("C7:C{$last}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E7:F{$last}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("C7:F{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    public function buildTransfers(string $printedAtLabel): Spreadsheet
    {
        $report = new BranchStockReport($this->branch);
        $rows = $report->transferRows();
        $totals = $report->transferTotals();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Transfers');

        $sheet->setCellValue('A1', 'DICNHS-TEMPUCO');
        $sheet->setCellValue('A2', 'BRANCH TRANSFERS — '.$this->branch->name);
        $sheet->setCellValue('A3', __('Branch :number · :code', [
            'number' => $this->branch->branch_number,
            'code' => $this->branch->code,
        ]));
        $sheet->setCellValue('A4', $printedAtLabel);

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);

        $headers = ['DATE', 'SKU', 'PRODUCT', 'QTY TRANSFERRED', 'EXPIRES', 'RECORDED BY'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 6], $header);
        }

        $sheet->getStyle('A6:F6')->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '5B9BD5']],
            ],
        ]);

        $rowNumber = 7;

        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNumber}", PhilippineTime::format($row->created_at));
            $sheet->setCellValue("B{$rowNumber}", (string) ($row->inventoryItem?->sku ?? ''));
            $sheet->setCellValue("C{$rowNumber}", (string) ($row->inventoryItem?->name ?? __('Unknown product')));
            $sheet->setCellValue("D{$rowNumber}", $row->quantity);
            $sheet->setCellValue("E{$rowNumber}", $row->expiration_date?->format('Y-m-d') ?? '');
            $sheet->setCellValue("F{$rowNumber}", (string) ($row->recordedBy?->name ?? ''));
            $rowNumber++;
        }

        $sheet->setCellValue("B{$rowNumber}", __('Products transferred'));
        $sheet->setCellValue("C{$rowNumber}", $totals['products']);
        $sheet->setCellValue("D{$rowNumber}", $totals['units']);
        $sheet->getStyle("A{$rowNumber}:F{$rowNumber}")->getFont()->setBold(true);

        $last = max(7, $rowNumber);
        $sheet->getStyle("C{$rowNumber}:D{$last}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("D7:D{$last}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    private function download(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
