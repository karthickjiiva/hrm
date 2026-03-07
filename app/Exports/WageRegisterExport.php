<?php

namespace App\Exports;

use App\Models\StaffMember;
use App\Models\PayrollNew;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class WageRegisterExport implements FromCollection, WithEvents, WithColumnWidths, WithCustomStartCell
{
    protected $month, $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year  = $year;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function collection()
    {
        $employees   = StaffMember::where('name', '!=', 'Admin')->get();
        $paymentDate = Carbon::createFromDate($this->year, $this->month, 1)
            ->endOfMonth()
            ->format('d-m-Y');

        return $employees->map(function ($user, $index) use ($paymentDate) {

            $payroll = PayrollNew::where('employee_id', $user->id)
                ->where('month', $this->month)
                ->where('year', $this->year)
                ->first();

            $pf = (float)($payroll?->pf_employee ?? 0);
            $pt = (float)($payroll?->professional_tax ?? 0);
            
            $loandeduct = $payroll?->loan_deduct;
            $advance_deduct = $payroll?->advance_deduct;
            
            $totaladvance = $loandeduct + $advance_deduct;

            return [
                $index + 1,
                strtoupper($user->name),
                $user->employee_number ?? '-',
                $payroll?->days_payable ?? 0,
                $this->formatAmount($payroll?->basic ?? 0),
                $this->formatAmount(0), // DA
                $this->formatAmount($payroll?->hra ?? 0),
                $this->formatAmount($payroll?->allowance ?? 0),
                $this->formatAmount(0), // OT Wages
                $this->formatAmount(0), // OT Avail
                $this->formatAmount($payroll?->total_earnings ?? 0),

                $this->formatAmount($pf),
                $this->formatAmount(0), // ESI
                $this->formatAmount(0), // LWF

                $this->formatAmount($totaladvance == 0 ? 0 : $totaladvance), // advance
                $this->formatAmount(0), 
                $this->formatAmount(0), 
                $this->formatAmount(0),
                $this->formatAmount(0), 
                $this->formatAmount(0), 
                $this->formatAmount(0), 
                $this->formatAmount(0),

                $this->formatAmount($pt),
                $this->formatAmount($pf + $pt),
                $this->formatAmount($payroll?->net_salary ?? 0),
                $paymentDate,
            ];
        });
    }

  private function formatAmount($value)
    {
        $rounded = round((float)$value, 0); // Rounds to nearest whole number
        return number_format($rounded, 2, '.', ''); // Formats to 3311.00
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,  'B' => 28, 'C' => 14, 'D' => 10,
            'E' => 10, 'F' => 8,  'G' => 10, 'H' => 10,
            'I' => 10, 'J' => 10, 'K' => 12,
            'L' => 8,  'M' => 8,  'N' => 8,
            'O' => 10, 'P' => 10, 'Q' => 10, 'R' => 10,
            'S' => 10, 'T' => 10, 'U' => 10, 'V' => 10,
            'W' => 10, 'X' => 10, 'Y' => 12, 'Z' => 14,
        ];
    }

     public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                /* ===== 1. MERGE GROUP HEADERS ===== */
                $sheet->mergeCells('L1:X1');
                $sheet->setCellValue('L1', 'DEDUCTIONS');
                
                $sheet->mergeCells('O2:R2');
                $sheet->setCellValue('O2', 'ADVANCES');
                
                $sheet->mergeCells('S2:V2');
                $sheet->setCellValue('S2', 'DAMAGES / FINES');

                /* ===== 2. STYLE GROUP HEADERS (CENTERED) ===== */
                $sheet->getStyle('L1:X2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                /* ===== 3. VERTICAL MERGES FOR FIXED HEADERS ===== */
                $mergeRanges = [
                    'A1:A3','B1:B3','C1:C3','D1:D3','E1:E3','F1:F3','G1:G3',
                    'H1:H3','I1:I3','J1:J3','K1:K3',
                    'L2:L3','M2:M3','N2:N3',
                    'W2:W3','X2:X3','Y1:Y3','Z1:Z3'
                ];
                foreach ($mergeRanges as $range) { $sheet->mergeCells($range); }

                /* ===== 4. HEADER TEXT & INDIVIDUAL ROTATION ===== */
                $headers = [
                'A1' => 'Sl.No',
                'B1' => 'Name of the Employee',
                'C1' => 'Emp Code',
                'D1' => 'Days Worked',
                'E1' => 'Basic Wage',
                'F1' => 'DA',
                'G1' => 'HRA',
                'H1' => 'Other Allowance',
                'I1' => 'OT Wages',
                'J1' => 'OT Avail',
                'K1' => 'Gross Wages',
                'L2' => 'PF',
                'M2' => 'ESI',
                'N2' => 'LWF',
                'O3' => 'Advance Paid',
                'P3' => 'Advance At Beginning of month',
                'Q3' => 'Advance Recovered',
                'R3' => 'Advance Pending',
                'S3' => 'Damage Amount',
                'T3' => 'Recovery Pending',
                'U3' => 'Deduction Made',
                'V3' => 'Pending Recovery',
                'W2' => 'Other Deductions',
                'X2' => 'Total Deductions',
                'Y1' => 'Net Wages',
                'Z1' => 'Date of Payment',
            ];


                foreach ($headers as $cell => $text) {
                    $sheet->setCellValue($cell, $text);
                    
                    // Column B (Name) and Z (Date) are straight, others are rotated
                    $rotation = (str_starts_with($cell, 'B')) ? 0 : 90;

                    $sheet->getStyle($cell)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 9],
                        'alignment' => [
                            'horizontal'   => Alignment::HORIZONTAL_CENTER,
                            'vertical'     => Alignment::VERTICAL_CENTER,
                            'textRotation' => $rotation,
                            'wrapText'     => true,
                        ],
                    ]);
                }

                /* ===== 5. GENERAL STYLING (HEIGHTS, BORDERS, FREEZE) ===== */
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(25);
                $sheet->getRowDimension(3)->setRowHeight(95);

                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:Z{$highestRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                
                // Align data rows
                $sheet->getStyle("A4:Z{$highestRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->freezePane('A4');
            },
        ];
    }
}