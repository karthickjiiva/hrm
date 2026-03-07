<?php

namespace App\Exports;

use App\Models\User;
use App\Models\StaffMember;
use App\Models\PayrollNew;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class BonusReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithColumnFormatting
{
    protected $year;

    public function __construct($year) {
        $this->year = $year;
    }

    public function columnWidths(): array {
        return [
            'A' => 8, 'B' => 30, 'C' => 15, 'D' => 15, 'E' => 25,
            'F' => 15,'G' => 15,'H' => 15, 'I' => 10, 'J' => 20, 'K' => 10, 'L' => 18,
            'M' => 15, 'N' => 15, 'O' => 15, 'P' => 15, 'Q' => 15,
        ];
    }

    /**
     * This ensures the columns show as 0.00 format in Excel
     */
    public function columnFormats(): array {
        return [
            'F' => NumberFormat::FORMAT_NUMBER_00, // New Gross
            'M' => NumberFormat::FORMAT_NUMBER_00, // Basic 60%
            'N' => NumberFormat::FORMAT_NUMBER_00, // (B*1)
            'O' => NumberFormat::FORMAT_NUMBER_00, // New Year
            'P' => NumberFormat::FORMAT_NUMBER_00, // Deepavali
            'Q' => NumberFormat::FORMAT_NUMBER_00, // Pongal
        ];
    }

// 1. Get all payrolls for the Financial Year (April of Prev Year to March of Selected Year)
// $payrolls = PayrollNew::where('employee_id', $user->id)
//     ->where(function($query) {
//         $query->where(function($q) {
//             // April (4) to December (12) of the previous year
//             $q->where('year', $this->year - 1)
//               ->where('month', '>=', 4);
//         })
//         ->orWhere(function($q) {
//             // January (1) to March (3) of the selected year
//             $q->where('year', $this->year)
//               ->where('month', '<=', 3);
//         });
//     })
//     ->orderBy('year', 'desc')
//     ->orderBy('month', 'desc')
//     ->get();
    
    public function collection()
    {
       // $users = User::with(['designation'])->get();
        $users = StaffMember::with(['designation'])
        ->where('user_type', 'staff_members') // Filter for staff only
        ->get();
        return $users->map(function ($user, $index) {
            $payrolls = PayrollNew::where('employee_id', $user->id)
                                  ->where('year', $this->year)
                                  ->orderBy('month', 'desc')
                                  ->get();

            if ($payrolls->isEmpty()) return null;

          //  $totalLop = $payrolls->sum('loss_of_pay_days');
            $totalLop = (float) ($payrolls->sum('loss_of_pay_days') ?? 0);
            $latestPayroll = $payrolls->first(); 
            
            $netDays = 365 - $totalLop; 
            $finalnetdays = $netDays - $totalLop; 
            $annualBasic = $latestPayroll->basic;
            
            $b1Value = ($annualBasic * $netDays) / 365;

            // Math logic: Round to nearest whole number, but stored as float for Excel formatting
            return [
                'S.No' => $index + 1,
                'NAME' => $user->name,
                'DOJ' => $user->joining_date,
                'DOC' => '--', 
                'DESIGNATION' => $user->designation->name ?? '-',
                'NEW GROSS' => (float) $latestPayroll->total_earnings,
                'ELIGIBLE DATE' => '',
                'RESIGNED DATE' => '',
                'A (365)' => 365,
                'WORKING DAYS' => 365 - $totalLop,
                'LOP' => (float) $totalLop,
                'NET DAYS' => $finalnetdays,
                'BASIC 60%' => (float) $annualBasic,
                '(B * 1)' => (float) round($b1Value, 0),
                'NEW YEAR' => (float) round($b1Value * 0.25, 0),
                'DEEPAVALI' => (float) round($b1Value * 0.50, 0),
                'PONGAL' => (float) round($b1Value * 0.25, 0),
            ];
        })->filter()->values();
    }

    public function headings(): array {
        return [
            'S.No', 'NAME', 'DOJ', 'DOC', 'DESIGNATION', 'New Gross', 
            'Eligible date', 'Resigned date', 'A (365)', 'Total No of days working', 
            'LOP', 'Net Days for Bonus', 'Basic 60%', '( B * 1 )', 
            'New year', 'Deepavali', 'Pongal'
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->getRowDimension(1)->setRowHeight(45);
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9D9D9'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        return [];
    }
}