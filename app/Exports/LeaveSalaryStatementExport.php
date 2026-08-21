<?php
namespace App\Exports;

use App\Models\LeaveSalaryStatement;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LeaveSalaryStatementExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $year;

    public function __construct($year) {
        $this->year = $year;
    }

    public function collection() {
        return LeaveSalaryStatement::with('employee')
            ->where('year', $this->year)
            ->get();
    }

    public function map($snapshot): array {
        static $no = 0;
        $no++;
        return [
            $no,
            $snapshot->employee->name ?? '-',
            $snapshot->employee->joining_date ? date('d-M-Y', strtotime($snapshot->employee->joining_date)) : '-',
            $snapshot->employee->employee_number ?? '-',
            round($snapshot->new_gross),
            $snapshot->earned_leave,
            round($snapshot->leave_salary),
        ];
    }

    public function headings(): array {
        return [
            ["Leave Salary {$this->year}"],
            ["#", "Name", "Joining Date", "Emp Code", "New Gross", "Earned Leave", "Leave Salary"]
        ];
    }

    public function styles(Worksheet $sheet) {
        $sheet->mergeCells('A1:G1');

        return [
            1 => [
                'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'] 
                ]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 16],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9EAD3'] 
                ]
            ],
        ];
    }
}
?>