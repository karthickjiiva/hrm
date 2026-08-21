<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use App\Models\PayrollNew;

class PayrollExport implements 
    FromCollection, 
    WithHeadings, 
    WithStyles, 
    WithColumnWidths,
    WithColumnFormatting,
    WithEvents
{
    protected $payrolls;

    public function columnWidths(): array
    {
        return [
            'B' => 25, 
            'C' => 15, 
            'D' => 18, 
            'Q' => 14, 
            'X' => 14, 
        ];
    }

    public function __construct($payrolls)
    {
        $this->payrolls = $payrolls;
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_00, // NEW GROSS
            'F' => NumberFormat::FORMAT_NUMBER_00, // BASIC
            'G' => NumberFormat::FORMAT_NUMBER_00, // LIMIT
            'H' => NumberFormat::FORMAT_NUMBER_00, // HRA
            'I' => NumberFormat::FORMAT_NUMBER_00, // CONVEY
            'J' => NumberFormat::FORMAT_NUMBER_00, // GROSS PAY
            'K' => NumberFormat::FORMAT_NUMBER_00, // PF
            'L' => NumberFormat::FORMAT_NUMBER_00, // ESI
            'P' => NumberFormat::FORMAT_NUMBER_00, // LOAN
            'Q' => NumberFormat::FORMAT_NUMBER_00, // SITE ADVANCE
'R' => NumberFormat::FORMAT_NUMBER_00, // SALARY ADVANCE
'S' => NumberFormat::FORMAT_NUMBER_00, // TDS
'T' => NumberFormat::FORMAT_NUMBER_00, // PRO TAX
'U' => NumberFormat::FORMAT_NUMBER_00, // NET SALARY
'V' => NumberFormat::FORMAT_NUMBER_00, // GROSS SALARY CHECK
'X' => NumberFormat::FORMAT_NUMBER_00, // NET SALARY (2)
        ];
    }

    public function collection()
    {
        return $this->payrolls->map(function ($payroll, $index) {
            return [
                'SL' => $index + 1,
                'NAME' => $payroll->employee->name ?? '-',
                'EMP NO' => $payroll->employee->employee_number ?? '-', 
                'DESIGNATION' => $payroll->employee->designation->name ?? '-',
                'NEW GROSS' => $this->formatAmount($payroll->total_earnings),
                'BASIC 60%' => $this->formatAmount($payroll->basic),
                'LIMIT' => 15000, 
                'HRA 30%' => $this->formatAmount($payroll->hra),
                'CONVEY 10%' => $this->formatAmount($payroll->allowance),
                'GROSS PAY' => $this->formatAmount($payroll->total_earnings),
                'PF' => $this->formatAmount($payroll->pf_employee),
                'ESI' => $this->formatAmount($payroll->esi_employee),
                'NO OF DAYS FOR THE MONTH' => $payroll->total_working_days,
                'DAYS OF LOP' => $payroll->loss_of_pay_days,
                'ACTUAL DAYS OF SALARY' => $payroll->actual_payable_days,
                'LOAN' => $this->formatAmount($payroll->loan_deduct),
                'SITE ADVANCE' => $this->formatAmount($payroll->site_advance_deduct),
                'SALARY ADVANCE' => $this->formatAmount($payroll->salary_advance_deduct),
                'TDS' => $this->formatAmount($payroll->tds),
                'PRO TAX' => $this->formatAmount($payroll->professional_tax),
                'NET SALARY' => $this->formatAmount($payroll->net_salary), 
                'GROSS SALARY CHECK' => $this->formatAmount($payroll->total_earnings),
                'OTHERS' => '',
                'NET SALARY (2)' => $this->formatAmount($payroll->net_salary), 
                'REMARKS' => '',
            ];
        });
    }

    private function formatAmount($value)
    {
        return round($value ?? 0, 0);  
    }

    public function headings(): array
    {
        return [
            'SL', 'NAME', 'EMP NO', 'DESIGNATION', 'NEW GROSS', 'BASIC 60%', 'LIMIT',
            'HRA 30%', 'CONVEY 10%', 'GROSS PAY', 'PF', 'ESI',
            'NO OF DAYS FOR THE MONTH', 'DAYS OF LOP', 'ACTUAL DAYS OF SALARY',
            'LOAN', 'SITE ADVANCE', 'SALARY ADVANCE', 'TDS', 'PRO TAX', 'NET SALARY', 'GROSS SALARY CHECK',
            'OTHERS', 'NET SALARY (2)', 'REMARKS',
        ];
    }

    public function styles(Worksheet $sheet)
    { 
        $sheet->getRowDimension(1)->setRowHeight(80);
     
        $sheet->getStyle('A1:X1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'D9D9D9',  
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'AAAAAA'],
                ],
            ],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Numeric columns — skip V (OTHERS) and X (REMARKS)
                $numericCols = ['E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','X'];
                foreach ($numericCols as $col) {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $cell = $sheet->getCell("{$col}{$row}");
                        $val  = $cell->getValue();
                        if ($val === null || $val === '') {
                            $cell->setValueExplicit(0, DataType::TYPE_NUMERIC);
                        } else {
                            $cell->setValueExplicit((float)$val, DataType::TYPE_NUMERIC);
                        }
                        $sheet->getStyle("{$col}{$row}")
                            ->getNumberFormat()
                            ->setFormatCode('0.00');
                    }
                }
            },
        ];
    }
}