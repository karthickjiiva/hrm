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
            'F' => 15, 'G' => 15, 'H' => 15, 'I' => 10, 'J' => 20, 'K' => 10, 'L' => 18,
            'M' => 15, 'N' => 15, 'O' => 15, 'P' => 15, 'Q' => 15,
        ];
    }

    /**
     * Updated to FORMAT_NUMBER (no decimals) since all values are now rounded
     */
    public function columnFormats(): array {
        return [
            'F' => NumberFormat::FORMAT_NUMBER, // New Gross
            'M' => NumberFormat::FORMAT_NUMBER, // Basic 60%
            'N' => NumberFormat::FORMAT_NUMBER, // (B*1)
            'O' => NumberFormat::FORMAT_NUMBER, // New Year
            'P' => NumberFormat::FORMAT_NUMBER, // Deepavali
            'Q' => NumberFormat::FORMAT_NUMBER, // Pongal
        ];
    }


    public function collection()
    {
       $users = StaffMember::with(['designation'])
        ->where('user_type', 'staff_members') 
        ->where('has_resigned', 0)
        ->get();

        return $users->map(function ($user, $index) {
            $payrolls = PayrollNew::where('employee_id', $user->id)
                                  ->where('year', $this->year)
                                  ->orderBy('month', 'desc')
                                  ->get();

            if ($payrolls->isEmpty()) return null;

            $totalLop = (float) ($payrolls->sum('loss_of_pay_days') ?? 0);
            $latestPayroll = $payrolls->first(); 
            
            $netDays = 365 - $totalLop; 
            $finalnetdays = $netDays - $totalLop; 
            $annualBasic = $latestPayroll->basic;
            
            $b1Value = ($annualBasic * $netDays) / 365;

            return [
                'S.No' => $index + 1,
                'NAME' => $user->name,
                'DOJ' => $user->joining_date,
                'DOC' => '--', 
                'DESIGNATION' => $user->designation->name ?? '-',
                'NEW GROSS' => (float) round($latestPayroll->total_earnings),  
                'ELIGIBLE DATE' => '',
                'RESIGNED DATE' => '',
                'A (365)' => 365,
                'WORKING DAYS' => 365 - $totalLop,
                'LOP' => (float) $totalLop,
                'NET DAYS' => $finalnetdays,
                'BASIC 60%' => (float) round($annualBasic),  
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