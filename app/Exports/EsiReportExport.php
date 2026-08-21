<?php

namespace App\Exports;

use App\Models\StaffMember;
use App\Models\PayrollNew;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class EsiReportExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $month, $year;

    public function __construct($month, $year) {
        $this->month = $month;
        $this->year = $year;
    }

     public function collection()
{
    $employees = StaffMember::where('esi_enabled', 1)
        ->where('name', '!=', 'Admin')
        ->where('has_resigned', 0)

       ->whereDate('joining_date', '<=', \Carbon\Carbon::create($this->year, $this->month, 1)->endOfMonth())
        ->get();
 
    return $employees->map(function ($user, $index) {
        $payroll = PayrollNew::where('employee_id', $user->id)
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->first();

       
       // 1. Base gross
$basic = (float) ($user->basic_salary ?? 0);
$hra   = (float) ($user->monthly_hra_percent_monthly ?? 0);
$grossBase = $basic + $hra;

// 2. Get total days + payable days
$totalDays   = (float) ($payroll?->total_working_days ?? 0);
$payableDays = (float) ($payroll?->days_payable ?? 0);

// 3. Per day salary
$perDay = $totalDays > 0 ? ($grossBase / $totalDays) : 0;

// 4. Final gross (pro-rated)
$grossSalary = round($perDay * $payableDays, 0);

// 5. ESI (0.75%)
$esiAmount = round(($grossSalary * 0.75) / 100, 0);


        return [
            'sl' => $index + 1,
            'name' => strtoupper($user->name),
            'esi_no' => $user->esi_number ?? '-',
            'working_days' => $payroll?->days_payable ?? 0,
            'gross' => $grossSalary,
            'esi' => (float)$esiAmount,
        ];
    });
}

    public function headings(): array {
        $dateStr = '1-' . date('M', mktime(0, 0, 0, $this->month, 10)) . '-' . substr($this->year, 2);
        
        return [
            [$dateStr], // Row 1
            ['Buildcraft Interior (P) Ltd           ESI No.51000863210001001'], // Row 2 (Single line)
            ['Sl.No.', 'Name', 'ESI No', 'Working days', 'Gross Salary', 'ESI'] // Row 3
        ];
    }

    public function columnWidths(): array {
        return ['A' => 10, 'B' => 45, 'C' => 25, 'D' => 18, 'E' => 18, 'F' => 18];
    }

    public function styles(Worksheet $sheet) {
        return [
            // Style the Table Headings (Row 3)
            3 => [
                'font' => ['bold' => true, 'italic' => true, 'size' => 13],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // 1. Merge Row 1 & 2 across all columns
                $sheet->mergeCells('A1:F1');
                $sheet->mergeCells('A2:F2');
                
                $sheet->getStyle('A1:F3')->getAlignment()->applyFromArray([
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ]);
                // 2. Formatting Header Rows (1, 2, 3)
                $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setItalic(true)->setSize(14);
                $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 3. Global Data Styling (Row 4 onwards)
                $dataRange = "A4:F$highestRow";
                $sheet->getStyle($dataRange)->getFont()->setSize(12); // Bigger data font
                $sheet->getStyle($dataRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("A4:A$highestRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("D4:F$highestRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // 4. Increase Row Heights for "breathing room"
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(25);
                $sheet->getRowDimension(3)->setRowHeight(32);
                for ($i = 4; $i <= $highestRow; $i++) {
                    $sheet->getRowDimension($i)->setRowHeight(28); // Taller data rows
                }

                // 5. Borders & Background
                $sheet->getStyle("A1:F$highestRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle('A1:F3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('EFEFEF');
            },
        ];
    }
}