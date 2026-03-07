<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\PayrollNew;
use Carbon\Carbon;

class PfreportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithStrictNullComparison, WithCustomStartCell
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function columnWidths(): array
    {
         return [
        'A' => 6,
        'B' => 20, 
        'C' => 25, 
        'D' => 20,
        'E' => 20,
        'F' => 20,
        'G' => 20,
        'H' => 25,
        'I' => 25,
        'J' => 15,
        'K' => 25,
        'L' => 20,
    ];
    }

 
    
    public function collection()
    {
        $payrolls = PayrollNew::with(['employee.employeeType'])
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->get();
    
        $filteredPayrolls = $payrolls->filter(function ($payroll) {
            $employee = $payroll->employee;
            $employeeType = $employee->employeeType->type ?? '';
            if (trim($employee->name ?? '') === 'Admin') return false;
            if (trim($employeeType) === 'Consultant Emp') return false;
    
            return true;
        })->values();
    
        return $filteredPayrolls->map(function ($payroll, $index) {
            $employee = $payroll->employee;
            $employeeType = $employee->employeeType;
            $pfPensionScheme = $employeeType->pf_pension_scheme ?? null;
    
            $epsWages = ceil($payroll->basic > 15000 ? 15000.0 : ($payroll->basic ?? 0));
            $edliWages = ceil($payroll->basic > 15000 ? 15000.0 : ($payroll->basic ?? 0));
            $eeShare = ceil(($payroll->basic ?? 0) * 0.12);
            $epsContribution = ceil($epsWages * 0.0833);
            $edliShare = ceil($edliWages * 0.03666);
    
            $epsContributionDisplay = $epsContribution;
            $erShare = $edliShare;
            $epsWages1 = $epsWages;
            if (strcasecmp(trim($employeeType->type ?? ''), 'PF 1800') === 0) {
                 if ($payroll->basic > 15000) {
                    $eeShare = 1800; 
                }
                $epsContributionDisplay = 0;
                $epsWages1 = 0;
                $erShare = $edliShare + $epsContribution;
            }
    
            if ($pfPensionScheme != 1) {
                $epsContributionDisplay = 0;
                $epsWages1 = 0;
                $erShare = $edliShare + $epsContribution;
            }
    
            return [
                'SL' => $index + 1,
                'UAN NUMBER' => " " . ($employee->uan_number ?? ''),
                'MEMBER NAME' => $employee->name ?? '-',
                'GROSS WAGES' => ceil($payroll->total_earnings ?? 0),
                'EPF WAGES' => $epsWages,
                'EPS WAGES' => $epsWages1,
                'EDLI WAGES' => $edliWages,
                'EE SHARE REMITTED' => $eeShare,
                'EPS CONTRIBUTION REMITTED' => $epsContributionDisplay,
                'ER SHARE REMITTED' => $erShare,
                'NCP DAYS' => $payroll->loss_of_pay_days ?? 0,
                'REFUND OF ADVANCE' => '0',
            ];
        });
    }


    public function headings(): array
    {
        return [
            'SL',
            'UAN NUMBER', 
            'MEMBER NAME', 
            'GROSS WAGES',
            'EPF WAGES',
            'EPS WAGES',
            'EDLI WAGES',
            'EE SHARE REMITTED',
            'EPS CONTRIBUTION REMITTED',
            'ER SHARE REMITTED',
            'NCP DAYS',
            'REFUND OF ADVANCE',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', "PF Report for " . Carbon::create($this->year, $this->month, 1)->format('F Y'));
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50'],
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);

        $sheet->getRowDimension(2)->setRowHeight(40);
        $sheet->getStyle('A2:L1')->applyFromArray([
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
                'startColor' => ['rgb' => 'D9D9D9'],
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
