<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class AnnualPayrollMasterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithCustomStartCell, WithColumnFormatting, WithEvents
{
    protected $payrolls;
    protected $year;
    protected $rowNumber = 0;

    public function __construct($payrolls, $year)
    {
        $this->payrolls = $payrolls;
        $this->year = $year;
    }

    public function collection()
    {
        return $this->payrolls;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function headings(): array
    {
        return [
            'SL', 'NAME','EMP NO', 'DESIGNATION', 'TOTAL GROSS', 'BASIC', 'LIMIT',
            'HRA', 'FOOD ALLOW', 'CONVEYANCE', 'GROSS PAY', 'PF', 'ESI',
            'TOT WORKING DAYS', 'TOT LOP DAYS', 'TOT PAYABLE DAYS',
            'TDS', 'PRO TAX', 'NET SALARY', 'GROSS CHECK',
            'OTHERS', 'NET SALARY (2)', 'REMARKS',
        ];
    }

    public function map($payroll): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $payroll->employee->name ?? '-',
            $payroll->employee->employee_number ?? '-', 
            $payroll->employee->designation->name ?? '-',
            round($payroll->total_earnings),
            round($payroll->basic),
            15000 * 12, // Cumulative limit for the year
            round($payroll->hra),
            round($payroll->food_allowance),
            round($payroll->allowance),
            round($payroll->total_earnings),
            round($payroll->pf_employee),
            round($payroll->esi_employee),
            $payroll->total_working_days,
            $payroll->loss_of_pay_days,
            $payroll->actual_payable_days,
            round($payroll->tds),
            round($payroll->professional_tax),
            round($payroll->net_salary), 
            round($payroll->total_earnings),
            '',
            round($payroll->net_salary), 
            'Annual Summary',
        ];
    }

    public function columnFormats(): array
    {
        return [
            // Using FORMAT_NUMBER for whole numbers (rounded)
            'E' => NumberFormat::FORMAT_NUMBER,
            'F' => NumberFormat::FORMAT_NUMBER,
            'G' => NumberFormat::FORMAT_NUMBER,
            'H' => NumberFormat::FORMAT_NUMBER,
            'I' => NumberFormat::FORMAT_NUMBER,
            'J' => NumberFormat::FORMAT_NUMBER,
            'K' => NumberFormat::FORMAT_NUMBER,
            'L' => NumberFormat::FORMAT_NUMBER,
            'M' => NumberFormat::FORMAT_NUMBER,
            'Q' => NumberFormat::FORMAT_NUMBER,
            'R' => NumberFormat::FORMAT_NUMBER,
            'S' => NumberFormat::FORMAT_NUMBER,
            'T' => NumberFormat::FORMAT_NUMBER,
            'V' => NumberFormat::FORMAT_NUMBER,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style the Table Headings (Row 4)
        $sheet->getRowDimension(4)->setRowHeight(30);
        $sheet->getStyle('A4:W4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '52C41A'] // Green theme for Payroll
            ],
            'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
        ]);
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Big Title Header
                $sheet->mergeCells('A1:W2');
                $sheet->setCellValue('A1', "CUMULATIVE PAYROLL MASTER REPORT - {$this->year}");
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                ]);

                // Auto Size
                foreach (range('A', 'W') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}