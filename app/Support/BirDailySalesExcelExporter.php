<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BirDailySalesExcelExporter
{
    public function __construct(
        private readonly BirDailySalesReport $report,
    ) {}

    public function download(?string $filename = null): StreamedResponse
    {
        $printedAt = PhilippineTime::now();
        $filename ??= sprintf(
            'bir-daily-sales-%s-to-%s.xlsx',
            $this->report->fromDate()->format('Y-m-d'),
            $this->report->toDate()->format('Y-m-d'),
        );

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
        $grandTotal = $this->report->grandTotal($rows);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('BIR Daily Sales');
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Courier New')->setSize(11);

        $headerLines = [
            1 => 'DICNHS TEMPUCO',
            2 => 'RIZAL AVE. ZONE 2 POB.',
            3 => 'CITY OF DIGOS, CAPITAL DAVAO DEL SUR',
            4 => 'NONVAT REG TIN: 001-946-758-000',
            5 => 'MIN : 23080213290100888',
            7 => '- DAILY SALES -',
            8 => 'FROM '.$this->report->fromLabel().' TO '.$this->report->toLabel(),
        ];

        foreach ($headerLines as $row => $text) {
            $sheet->mergeCells("A{$row}:C{$row}");
            $sheet->setCellValue("A{$row}", $text);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A7')->getFont()->setBold(true);

        $sheet->setCellValue('C9', 'TERMINAL ID: T01');
        $sheet->setCellValue('C10', 'RESET COUNTER:');
        $sheet->getStyle('C9:C10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $headerRow = 12;
        $sheet->setCellValue("A{$headerRow}", 'DATE');
        $sheet->setCellValue("B{$headerRow}", 'LAST S.I. NO');
        $sheet->setCellValue("C{$headerRow}", 'NET AMOUNT');
        $sheet->getStyle("A{$headerRow}:C{$headerRow}")->getFont()->setBold(true);

        $rowNumber = $headerRow + 1;

        foreach ($rows as $row) {
            $sheet->setCellValue("A{$rowNumber}", $row['date']);
            $sheet->setCellValue("B{$rowNumber}", $row['last_si_no']);
            $sheet->setCellValue("C{$rowNumber}", $row['net_amount']);
            $rowNumber++;
        }

        $lastDataRow = max($headerRow + 1, $rowNumber - 1);
        $sheet->getStyle("C".($headerRow + 1).":C{$lastDataRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle("C".($headerRow + 1).":C{$lastDataRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $totalLabelRow = $rowNumber + 1;
        $sheet->mergeCells("A{$totalLabelRow}:C{$totalLabelRow}");
        $sheet->setCellValue("A{$totalLabelRow}", 'ACCUMULATED GRAND TOTAL');
        $sheet->getStyle("A{$totalLabelRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("A{$totalLabelRow}")->getFont()->setBold(true);

        $totalValueRow = $totalLabelRow + 1;
        $sheet->setCellValue("C{$totalValueRow}", $grandTotal);
        $sheet->getStyle("C{$totalValueRow}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("C{$totalValueRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$totalValueRow}")->getFont()->setBold(true);

        $printRow = $totalValueRow + 2;
        $sheet->setCellValue("C{$printRow}", 'PRINT: '.$printedAtLabel);
        $sheet->getStyle("C{$printRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $sheet->getColumnDimension('A')->setWidth(14);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(18);

        return $spreadsheet;
    }
}
