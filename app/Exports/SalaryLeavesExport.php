<?php

namespace App\Exports;

use App\Models\User;
use App\Models\MonthlyLeaveSummary;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class SalaryLeavesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithEvents, ShouldAutoSize
{
    protected $month;
    protected $year;

    public function __construct($month, $year)
    {
        $this->month = $month;
        $this->year  = $year;
    }

    public function collection()
    {
        return User::orderBy('id')->get();
    }

    public function map($user): array
    {
        static $rowIndex = 0;
        $rowIndex++;

        $leaveSummary = MonthlyLeaveSummary::where('employee_id', $user->id)
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->first();

        return [
            $rowIndex,
            $user->name,
            '18-Feb-08', // dummy DOJ
            'BCIPL-C-073', // dummy emp code
            // CL
            $leaveSummary->opening_cl ?? 0,
            $leaveSummary->availed_cl ?? 0,
            $leaveSummary->closing_cl ?? 0,
            // SL
            $leaveSummary->opening_sl ?? 0,
            $leaveSummary->availed_sl ?? 0,
            $leaveSummary->closing_sl ?? 0,
            // EL
            $leaveSummary->opening_el ?? 0,
            $leaveSummary->availed_el ?? 0,
            $leaveSummary->closing_el ?? 0,
            // Totals
            ($leaveSummary->total_availed ?? 0),
            $leaveSummary->closing_balance_total ?? 0,
            $leaveSummary->lop_days ?? 0,
        ];
    }

    public function headings(): array
    {
        $monthYear = date("M-y", strtotime("{$this->year}-{$this->month}-01"));

        return [
            [ // Row 1
                $monthYear, "", "", "",
                "Casual Leave", "", "",
                "Sick Leave", "", "",
                "Earned Leave", "", "",
                $monthYear, "", ""
            ],
            [ // Row 2
                "NO", "Name", "DOJ", "Emp Code",
                "Opening", "Availed", "Balance",
                "Opening", "Availed", "Balance",
                "Opening", "Availed", "Balance",
                "Total Availed", "Closing Balance", "LOP"
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 13], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Merge headers row 1
                $sheet->mergeCells('A1:D1'); // Feb-25 left
                $sheet->mergeCells('E1:G1'); // CL
                $sheet->mergeCells('H1:J1'); // SL
                $sheet->mergeCells('K1:M1'); // EL
                $sheet->mergeCells('N1:P1'); // Feb-25 Totals

                // Colors
                $sheet->getStyle('A1:D2')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9EAD3'); // light green for main

                $sheet->getStyle('E1:G2')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFDE9D9'); // CL

                $sheet->getStyle('H1:J2')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9EAD3'); // SL

                $sheet->getStyle('K1:M2')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF2CC'); // EL

                $sheet->getStyle('N1:P2')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFD9D9D9'); // Totals

                // Borders
                $sheet->getStyle('A1:P1000')->getBorders()->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }
        ];
    }
}
