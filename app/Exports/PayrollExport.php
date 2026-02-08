<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use App\Models\PayrollNew;

class PayrollExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $month;
    protected $year;

    public function columnWidths(): array
    {
        return [
            'B' => 25, 
            'D' => 30, 
        ];
    }

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
          $payrolls = PayrollNew::with(['employee.designation'])
        ->where('month', $this->month)
        ->where('year', $this->year)
        ->whereHas('employee', function ($query) {
            $query->where('has_resigned', 1)
                  ->where('name', '!=', 'Admin');
        })
        ->get();
        
        return $payrolls->map(function ($payroll, $index) {
            return [
                'SL' => $index + 1,
                'NAME' => $payroll->employee->name ?? '-',
                'EMP NO' => $payroll->employee->employee_number ?? '-', 
                'DESIGNATION' => $payroll->employee->designation->name ?? '-',
                'NEW GROSS' => $payroll->total_earnings,
                'BASIC 60%' => $payroll->basic,
                'LIMIT' => 15000, 
                'HRA 30%' => $payroll->hra,
                'FOOD ALLOW 6%' => $payroll->food_allowance,
                'CONVEY 4%' => $payroll->allowance,
                'GROSS PAY' => round($payroll->total_earnings),
                'PF' => $payroll->pf_employee,
                'ESI' => $payroll->esi_employee,
                'NO OF DAYS FOR THE MONTH' => $payroll->total_working_days,
                'DAYS OF LOP' => $payroll->loss_of_pay_days,
                'ACTUAL DAYS OF SALARY' => $payroll->actual_payable_days,
                'TDS' => $payroll->tds,
                'PRO TAX' => $payroll->professional_tax,
                'NET SALARY' => round($payroll->net_salary), 
                'GROSS SALARY CHECK' => $payroll->total_earnings,
                'OTHERS' => '',
                'NET SALARY (2)' => round($payroll->net_salary), 
                'REMARKS' => '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SL', 'NAME','EMP NO', 'DESIGNATION', 'NEW GROSS', 'BASIC 60%', 'LIMIT',
            'HRA 30%', 'FOOD ALLOW 6%', 'CONVEY 4%', 'GROSS PAY', 'PF', 'ESI',
            'NO OF DAYS FOR THE MONTH', 'DAYS OF LOP', 'ACTUAL DAYS OF SALARY',
            'TDS', 'PRO TAX', 'NET SALARY', 'GROSS SALARY CHECK',
            'OTHERS', 'NET SALARY (2)', 'REMARKS',
        ];
    }

   public function styles(Worksheet $sheet)
{ 
    $sheet->getRowDimension(1)->setRowHeight(40);
 
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

}
