<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\ArrearGenerated;
use Carbon\Carbon;

class ArrearPfExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithStrictNullComparison, WithCustomStartCell
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
        return 'A2'; // Headings start from row 2
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,   // SL
            'B' => 25,  // MEMBER NAME
            'C' => 20,  // GROSS WAGES
            'D' => 20,  // EPF WAGES
            'E' => 20,  // EPS WAGES
            'F' => 20,  // EDLI WAGES
            'G' => 20,  // EE SHARE REMITTED
            'H' => 25,  // EPS CONTRIBUTION REMITTED
            'I' => 25,  // ER SHARE REMITTED
        ];
    }

    public function collection()
    {
        $payrolls = ArrearGenerated::with(['employee.employeeType'])
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->get();

        $filteredPayrolls = $payrolls->filter(function ($payroll) {
            $employee = $payroll->employee;
            $employeeType = $employee->employeeType->type ?? '';

            // Exclude Admin and Consultant Emp
            if (trim($employee->name ?? '') === 'Admin') return false;
            if (trim($employeeType) === 'Consultant Emp') return false;

            return true;
        })->values(); // Reindex for consecutive SL

       return $filteredPayrolls->map(function ($payroll, $index) {
        $employee = $payroll->employee;
        $employeeType = $employee->employeeType->type ?? '';
        $epsWages = ceil($payroll->basic > 15000 ? 15000.0 : ($payroll->basic ?? 0));
        $edliWages = ceil($payroll->basic > 15000 ? 15000.0 : ($payroll->basic ?? 0));
        $eeShare = ceil(($payroll->basic ?? 0) * 0.12);
        $epsContribution = ceil($epsWages * 0.0833);
        $edliShare = ceil($edliWages * 0.03666);

        if (strcasecmp(trim($employeeType), 'PF 1800') === 0) {
            $erShare = $edliShare + $epsContribution;
            $epsContributionDisplay = 0;
            $epsWages = 0;
        } else {
            $erShare = $edliShare;
            $epsContributionDisplay = $epsContribution;
        }

            return [
                'SL' => $index + 1,
                'MEMBER NAME' => $employee->name ?? '-',
                'GROSS WAGES' => ceil($payroll->total_earnings ?? 0),
                'EPF WAGES' => ceil($payroll->basic ?? 0),
                'EPS WAGES' => $epsWages,
                'EDLI WAGES' => $edliWages,
                'EE SHARE REMITTED' => $eeShare,
                'EPS CONTRIBUTION REMITTED' => $epsContributionDisplay,
                'ER SHARE REMITTED' => $erShare,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'SL',
            'MEMBER NAME',
            'GROSS WAGES',
            'EPF WAGES',
            'EPS WAGES',
            'EDLI WAGES',
            'EE SHARE REMITTED',
            'EPS CONTRIBUTION REMITTED',
            'ER SHARE REMITTED',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // --- Title row ---
        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', "Arrear PF Report for " . Carbon::create($this->year, $this->month, 1)->format('F Y'));
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

        // --- Headings row (row 2) ---
        $sheet->getRowDimension(2)->setRowHeight(40);
        $sheet->getStyle('A2:I2')->applyFromArray([
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
