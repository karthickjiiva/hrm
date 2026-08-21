<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\User;
use Carbon\Carbon;

class ProfTaxExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithStrictNullComparison, WithCustomStartCell
{
    protected $users;
    protected $monthRange;
    protected $year;

    public function __construct($users, $monthRange, $year)
    {
        $this->users = $users;
        $this->monthRange = $monthRange;
        $this->year = $year;
    }

    public function startCell(): string
    {
        return 'A5'; 
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 40,
            'C' => 15,
            'D' => 20,
            'E' => 15,
            'F' => 20,
            'G' => 15,
            'H' => 15,
            'I' => 10,
            'J' => 10,
            'K' => 20,
            'L' => 15,
        ];
    }

    protected function calculateTax($halfYearlyIncome)
    {
        $slabs = [
            [21001, 30000, 135],
            [30001, 45000, 315],
            [45001, 60000, 690],
            [60001, 75000, 1025],
            [75001, 999999, 1250],
        ];

        foreach ($slabs as [$min, $max, $tax]) {
            if ($halfYearlyIncome >= $min && $halfYearlyIncome <= $max) {
                return $tax;
            }
        }

        return 0;
    }

    public function collection()
{
    $periodStart = $this->monthRange === "10-3"
        ? Carbon::create($this->year, 10, 1)
        : Carbon::create($this->year, 4, 1);

    $periodEnd = $this->monthRange === "10-3"
        ? Carbon::create($this->year + 1, 3, 31)
        : Carbon::create($this->year, 9, 30);

    $rows = $this->users
        ->filter(function ($user) use ($periodStart, $periodEnd) {
            if ($user->name === 'Admin') return false;

            if (!$user->employeeType || $user->employeeType->prof_tax_enabled == 0) return false;

            if ($user->has_resigned == 0 && $user->resignation_date) {
                $resignationDate = Carbon::parse($user->resignation_date);
                if ($resignationDate->lt($periodStart)) {
                    return false;
                }
            }

            return true;
        })
        ->values()
        ->map(function ($user, $index) use ($periodStart, $periodEnd) {

            $monthlySalary = $user->monthly_amount ?? 0;

            $joined = Carbon::parse($user->joining_date);
            $resignation = ($user->has_resigned && $user->resignation_date)
                ? Carbon::parse($user->resignation_date)
                : null;

            $monthsToCount = 6;

            if ($joined->gt($periodStart)) {
                $monthsToCount = $periodEnd->month - $joined->month + 1;
                $monthsToCount = max(1, $monthsToCount);
            }

            if ($resignation && $resignation->between($periodStart, $periodEnd)) {
                $monthsToCount = 6;
            }

            $halfYearlyIncome = $monthlySalary * $monthsToCount;
            $tax = $this->calculateTax($halfYearlyIncome);

            // return [
            //     'SL' => $index + 1,
            //     'NAME & DESIGNATION' => $user->name ?? '-',
            //     'GROSS HALF-YEARLY INCOME' => $halfYearlyIncome,
            //     'AMOUNT OF TAX DEDUCTED & PAID' => $tax,
            //     'TOTAL AMOUNT' => $tax,
            //     'DETAILS OF PAYMENT' => 'Paid by ICICI Bank Chennai',
            //     'EMPLOYEE NO' => $user->employee_number ?? '-',
            //     'EMPLOYEE NAME' => $user->name ?? '-',
            //     'MONTHLY SALARY' => $monthlySalary,
            //     'MONTHS' => $monthsToCount,
            //     'GROSS HALF-YEARLY INCOME (DETAIL)' => $halfYearlyIncome,
            //     'TAX DEDUCTED' => $tax,
            // ];
            
            return [
                'SL' => $index + 1,
                'NAME & DESIGNATION' => $user->name ?? '-',
                'GROSS HALF-YEARLY INCOME' => round($halfYearlyIncome),
                'AMOUNT OF TAX DEDUCTED & PAID' => round($tax),
                'TOTAL AMOUNT' => round($tax),
                'DETAILS OF PAYMENT' => 'Paid by ICICI Bank Chennai',
                'EMPLOYEE NO' => $user->employee_number ?? '-',
                'EMPLOYEE NAME' => $user->name ?? '-',
                'MONTHLY SALARY' => round($monthlySalary),
                'MONTHS' => $monthsToCount,
                'GROSS HALF-YEARLY INCOME (DETAIL)' => round($halfYearlyIncome),
                'TAX DEDUCTED' => round($tax),
            ];
        });

    $rows->push([
        'SL' => $rows->count() + 1,
        'NAME & DESIGNATION' => 'Buildcraft',
        'GROSS HALF-YEARLY INCOME' => 0,
        'AMOUNT OF TAX DEDUCTED & PAID' => 1250,
        'TOTAL AMOUNT' => 1250,
        'DETAILS OF PAYMENT' => 'Paid by ICICI Bank Chennai',
        'EMPLOYEE NO' => '-',
        'EMPLOYEE NAME' => 'Buildcraft',
        'MONTHLY SALARY' => '-',
        'MONTHS' => '6',
        'GROSS HALF-YEARLY INCOME (DETAIL)' => '-',
        'TAX DEDUCTED' => '1250',
    ]);

    return $rows;
}

    public function headings(): array
    {
        return [
            'Sl.No',
            'Name and Designation of the employer or officer',
            'Gross half-yearly Income',
            'Amount of Tax Deducted & Paid',
            'Total Amount',
            'Details of challan / payment',
            'Employee Number',
            'Name',
            'Salary',
            'Month',
            'Gross half-yearly Income',
            'Amount of Tax Deducted',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', "Name of the company / organization or Central / State Government Office where Employees are working with address\nM/s.Buildcraft Interior Pvt. Ltd - 8th Floor KRM Centre,No.2 Harrington Road,Chetpet, Chennai - 600 031");
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);

        $rangeLabel = $this->monthRange === "10-3"
            ? "October {$this->year} to March " . ($this->year + 1)
            : "April to September {$this->year}";

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', "For the Half-Year Period : {$rangeLabel}");

        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', "T.N.A.N : 09 - 110 - PE - 09729");

        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->getRowDimension(2)->setRowHeight(30);
        $sheet->getRowDimension(3)->setRowHeight(25);
        $sheet->getRowDimension(4)->setRowHeight(20);

        $sheet->getStyle('A1:F3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'borders' => [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle('A5:L5')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
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
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $sheet->getStyle("A5:{$highestCol}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        $sheet->getStyle("F1:F{$highestRow}")->applyFromArray([
            'borders' => [
                'right' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [];
    }
}
