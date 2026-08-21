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

class EmployeeMasterExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithCustomStartCell, WithEvents
{
    protected $users;
    protected $rowNumber = 0;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function collection()
    {
        return $this->users;
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function headings(): array
    {
        return [
            '#',
            'Full Name',
            'Emp ID',
            'Phone',
            'Email',
            'DOB',
            'Joining Date',
            'Emp Type',
            'Designation',
            'Location',
            'Aadhaar No',
            'PAN No',
            'UAN (PF) No',
            'ESI No',
            'Basic Salary',
            'HRA',
            'PF Enabled',
            'ESI Enabled',
            'Monthly TDS',
            'Monthly Prof Tax'
        ];
    }

    public function map($user): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $user->name,
            $user->employee_number,
            $user->phone,
            $user->email,
            $user->dob ? $user->dob : 'N/A',
            $user->joining_date ? $user->joining_date : 'N/A',
            $user->employeeType ? $user->employeeType->name : 'N/A',
            $user->designation ? $user->designation->name : 'N/A', 
            $user->location ? $user->location->name : 'N/A',
            $user->aadhar_number,
            $user->pan_number,
            $user->uan_number,
            $user->esi_number,
            round($user->basic_salary),
            round($user->hra),
            $user->pf_enabled ? 'Yes' : 'No',
            $user->esi_enabled ? 'Yes' : 'No',
            round($user->monthly_tds),
            round($user->monthly_prof_tax),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            4 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1890FF']
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->mergeCells('A1:T2');
                $sheet->setCellValue('A1', 'EMPLOYEE MASTER DATA REPORT');
                
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '001529']
                    ],
                ]);

                foreach (range('A', 'T') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
    }
}