<?php

namespace App\Exports;

use App\Models\User;
use App\Models\MonthlyLeaveSummary;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class FormXLeaveRegisterExport implements FromCollection, WithEvents, WithColumnWidths, WithCustomStartCell
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
        return User::with('employeeType')
            ->where('name', '!=', 'Admin')
            ->whereHas('employeeType', fn ($q) => $q->where('type', '!=', 'Consultant Emp'))
            ->orderBy('id')
            ->get()
            ->map(function ($user, $index) {

                $ls = MonthlyLeaveSummary::where('employee_id', $user->id)
                    ->where('month', $this->month)
                    ->where('year', $this->year)
                    ->first();

                return [
                    $index + 1,
                    strtoupper($user->name),
                    $user->employee_number ?? '-',

                    (float)($ls->opening_el ?? 0),
                    (float)($ls->earned_el ?? 0),
                    (float)($ls->availed_el ?? 0),
                    (float)($ls->closing_el ?? 0),

                    (float)($ls->opening_sl ?? 0),
                    (float)($ls->availed_sl ?? 0),
                    (float)($ls->closing_sl ?? 0),

                    (float)($ls->opening_cl ?? 0),
                    (float)($ls->availed_cl ?? 0),
                    (float)($ls->closing_cl ?? 0),

                    0, 0, 0, 0, 0,
                    0, 0, 0,
                    '',
                ];
            });
    }

    public function columnWidths(): array
    {
        return [
            'A'=>5,'B'=>28,'C'=>15,
            'D'=>6,'E'=>6,'F'=>6,'G'=>6,
            'H'=>6,'I'=>6,'J'=>6,
            'K'=>6,'L'=>6,'M'=>6,
            'N'=>6,'O'=>6,'P'=>6,'Q'=>6,'R'=>6,
            'S'=>6,'T'=>6,'U'=>6,
            'V'=>12,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
            
                /* ===== GROUP HEADERS ===== */
                $sheet->mergeCells('D1:G1'); $sheet->setCellValue('D1', 'EARNED LEAVE');
                $sheet->mergeCells('H1:J1'); $sheet->setCellValue('H1', 'MEDICAL LEAVE');
                $sheet->mergeCells('K1:M1'); $sheet->setCellValue('K1', 'OTHER LEAVE');
                $sheet->mergeCells('N1:R1'); $sheet->setCellValue('N1', 'MATERNITY BENEFITS');
                $sheet->mergeCells('S1:U1'); $sheet->setCellValue('S1', 'GRATUITY BENEFITS');

                /* ===== FIXED HEADERS ===== */
                foreach (['A','B','C','V'] as $col) {
                    $sheet->mergeCells("{$col}1:{$col}3");
                }

                $sheet->setCellValue('A1', 'Sl.No');
                $sheet->setCellValue('B1', 'Name of the Employee');
                $sheet->setCellValue('C1', 'Emp No');
                $sheet->setCellValue('V1', 'Remarks');

                /* ===== SUB HEADERS ===== */
                $subHeaders = [
                    'D2'=>'Opening','E2'=>'Earned','F2'=>'Availed','G2'=>'Closing',
                    'H2'=>'Opening','I2'=>'Availed','J2'=>'Closing',
                    'K2'=>'Opening','L2'=>'Availed','M2'=>'Closing',
                    'N2'=>'Eligible Days','O2'=>'Leave Availed',
                    'P2'=>'Benefit Paid','Q2'=>'Balance','R2'=>'Remarks',
                    'S2'=>'Eligible','T2'=>'Paid','U2'=>'Balance',
                ];

                foreach ($subHeaders as $cell => $text) {
                    $col = preg_replace('/\d+/', '', $cell);
                    $sheet->setCellValue($cell, $text);
                    $sheet->mergeCells("{$col}2:{$col}3");
                }

                /* ===== STYLES ===== */

                // Group headers
                $sheet->getStyle('D1:U1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Sub headers rotated
                $sheet->getStyle('D2:U2')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 8],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'textRotation' => 90,
                    ],
                ]);

                // Sl.No & Emp No rotated ONLY (A and C)
                $sheet->getStyle('A1:A3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'textRotation' => 90,
                    ],
                ]);

                $sheet->getStyle('C1:C3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'textRotation' => 90,
                    ],
                ]);

                // ✅ Name of the Employee — STRAIGHT
                $sheet->getStyle('B1:B3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'textRotation' => 0,
                        'wrapText' => true,
                    ],
                ]);

                // Remarks straight
                $sheet->getStyle('V1:V3')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 9],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'textRotation' => 0,
                        'wrapText' => true,
                    ],
                ]);

                /* ===== ROW HEIGHTS ===== */
                $sheet->getRowDimension(1)->setRowHeight(26);
                $sheet->getRowDimension(2)->setRowHeight(55);
                $sheet->getRowDimension(3)->setRowHeight(12);

                /* ===== BORDERS ===== */
                $highestRow = $sheet->getHighestRow();
                $sheet->getStyle("A1:V{$highestRow}")
                    ->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->freezePane('A4');
            },
        ];
    }
}
