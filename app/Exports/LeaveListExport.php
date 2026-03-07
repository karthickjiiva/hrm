<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveListExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithCustomStartCell, WithEvents
{
    protected $leaves;
    protected $rowNumber = 0;
    protected $monthName;
    protected $year;

    public function __construct($leaves, $monthName, $year)
    {
        $this->leaves = $leaves;
        $this->monthName = $monthName;
        $this->year = $year;
    }

    public function collection()
    {
        return $this->leaves;
    }

    // Start the table on Row 4 to leave space for the title
    public function startCell(): string
    {
        return 'A3';
    }

    public function headings(): array
    {
        return [
            '#',
            'Name',
            'Emp. Number',
            'Date',
            'Leave Type',
            'Halfday'
        ];
    }

    public function map($leave): array
    {
        $this->rowNumber++;
        $leaveTypeName = $leave->leaveType ? $leave->leaveType->name : $leave->leave_type_name;

        return [
            $this->rowNumber,
            $leave->user->name ?? 'N/A',
            $leave->user->employee_number ?? 'N/A', 
            $leave->leave_date ? $leave->leave_date->format('d-m-Y') : 'N/A',
            $leaveTypeName,
            $leave->is_half_day ? 'Yes' : 'No',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the Table Headings (Row 4)
            3 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'] // Nice Blue Background
                ],
                'alignment' => ['horizontal' => 'center']
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // 1. Merge and Create Title (Row 1)
                $sheet->mergeCells('A1:F2');
                $title = "Leave List Report - {$this->monthName} - {$this->year}";
                $sheet->setCellValue('A1', $title);

                // 2. Style Title
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 3. Auto-size columns for "decoration"
                foreach (range('A', 'F') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}