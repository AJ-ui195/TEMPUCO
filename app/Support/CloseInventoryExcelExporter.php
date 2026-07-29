<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CloseInventoryExcelExporter
{
    public function __construct(
        private readonly CloseInventoryReport $report = new CloseInventoryReport,
    ) {}

    public function download(?string $filename = null): StreamedResponse
    {
        $printedAt = PhilippineTime::now();
        $filename ??= 'close-inventory-'.$printedAt->format('Y-m-d').'.xlsx';

        $spreadsheet = $this->build($printedAt->format('m/d/Y H:i:s'));

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function build(string $printedAtLabel): Spreadsheet
    {
        $rows = $this->report->rows();
        $totals = $this->report->totals($rows);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Close Inventory');

        $sheet->setCellValue('A1', 'DICNHS-TEMPUCO');
        $sheet->setCellValue('A2', 'GENERAL STOCKS INVENTORY');
        $sheet->setCellValue('A3', $printedAtLabel.' Page 1 of 1');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        $headers = [
            'ITEMCODE',
            'ITEMNAME',
            'S. PRICE',
            'BEG.',
            'SALES',
            'PULLOUT',
            'DEL.',
            'RETURN',
            'ADJ.',
            'BAL.',
            'U. COST',
            'TOTAL',
        ];

        $headerRow = 5;
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, $headerRow], $header);
        }

        $sheet->getStyle('A5:L5')->applyFromArray([
            'font' => ['bold' => true],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '5B9BD5']],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '5B9BD5']],
            ],
        ]);

        $rowNumber = $headerRow + 1;

        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNumber}", $row['item_code']);
            $sheet->setCellValue("B{$rowNumber}", $row['item_name']);
            $sheet->setCellValue("C{$rowNumber}", $row['selling_price']);
            $sheet->setCellValue("D{$rowNumber}", $row['beginning']);
            $sheet->setCellValue("E{$rowNumber}", $row['sales']);
            $sheet->setCellValue("F{$rowNumber}", $row['pullout']);
            $sheet->setCellValue("G{$rowNumber}", $row['delivery']);
            $sheet->setCellValue("H{$rowNumber}", $row['return']);
            $sheet->setCellValue("I{$rowNumber}", $row['adjustment']);
            $sheet->setCellValue("J{$rowNumber}", $row['balance']);
            $sheet->setCellValue("K{$rowNumber}", $row['unit_cost']);
            $sheet->setCellValue("L{$rowNumber}", $row['total']);
            $rowNumber++;
        }

        $totalsRow = $rowNumber;
        $sheet->setCellValue("C{$totalsRow}", $totals['selling_price']);
        $sheet->setCellValue("D{$totalsRow}", $totals['beginning']);
        $sheet->setCellValue("E{$totalsRow}", $totals['sales']);
        $sheet->setCellValue("F{$totalsRow}", $totals['pullout']);
        $sheet->setCellValue("G{$totalsRow}", $totals['delivery']);
        $sheet->setCellValue("H{$totalsRow}", $totals['return']);
        $sheet->setCellValue("I{$totalsRow}", $totals['adjustment']);
        $sheet->setCellValue("J{$totalsRow}", $totals['balance']);
        $sheet->setCellValue("K{$totalsRow}", $totals['unit_cost']);
        $sheet->setCellValue("L{$totalsRow}", $totals['total']);
        $sheet->getStyle("A{$totalsRow}:L{$totalsRow}")->getFont()->setBold(true);

        $moneyColumns = ['C', 'K', 'L'];
        $lastDataRow = max($headerRow + 1, $totalsRow);
        foreach ($moneyColumns as $column) {
            $sheet->getStyle("{$column}6:{$column}{$lastDataRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach (['D', 'E', 'F', 'G', 'H', 'I', 'J'] as $column) {
            $sheet->getStyle("{$column}6:{$column}{$lastDataRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        $sheet->getStyle("C6:L{$lastDataRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $signatureRow = $totalsRow + 3;
        $sheet->setCellValue("A{$signatureRow}", 'Received by:');
        $sheet->setCellValue("E{$signatureRow}", 'Verified by:');
        $sheet->setCellValue("I{$signatureRow}", 'Approved by:');

        $printedRow = $signatureRow + 2;
        $sheet->setCellValue("A{$printedRow}", 'PRINTED: '.$printedAtLabel);
        $sheet->getStyle("A{$printedRow}")->getFont()->setBold(true);

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }
}
