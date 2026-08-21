<?php

namespace App\Exports;

use App\Models\EmployeeInsurance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithColumnFormatting; 
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class EmployeeInsuranceExport implements 
    FromCollection, 
    WithHeadings, 
    WithMapping, 
    WithStyles, 
    WithEvents, 
    ShouldAutoSize,
    WithColumnFormatting
{
    public function collection()
    {
        return EmployeeInsurance::with('user')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            '#',
            'NAME',
            'EMP ID',
            'AADHAR NUMBER',
            'PAN NUMBER',
            'CONTACT NUMBER',
            'EDUCATION QUALIFICATION',
            'BLOOD GROUP',
            'DATE OF BIRTH',
            'EMERGENCY CONTACT NUMBER',
            'SPOUSE NAME',
            'SPOUSE DATE OF BIRTH',
            'SPOUSE AADHAR NUMBER',
            'CHILD 1 NAME',
            'CHILD 1 DOB',
            'CHILD 1 AADHAR NUMBER',
            'CHILD 2 NAME',
            'CHILD 2 DOB',
            'CHILD 2 AADHAR NUMBER',
            'PERMANENT ADDRESS',
            'COMMUNICATION ADDRESS',
            "FATHER'S NAME",
            'NOMINEE FOR ALL CLAIMS (NAME & RELATIONSHIP)'
        ];
    }

    public function map($insurance): array
    {
        static $index = 0;
        $index++;

        $user = $insurance->user;

        return [
            $index,
            $user->name ?? '-',
            $user->employee_number ?? '-',
            $user->aadhar_number ?? '-',
            $user->pan_number ?? '-',
            $user->phone ?? '-',
            $user->education ?? '-',
            $user->blood_group ?? '-',
            $user->dob ? date('d-M-Y', strtotime($user->dob)) : '-',
            $user->emergency_contact_number ?? '-',
            $insurance->spouse_name ?? '-',
            $insurance->spouse_dob ? date('d-M-Y', strtotime($insurance->spouse_dob)) : '-',
            " " . ($insurance->spouse_aadhar ?? '-'),
            $insurance->child1_name ?? '-',
            $insurance->child1_dob ? date('d-M-Y', strtotime($insurance->child1_dob)) : '-',
            " " . ($insurance->child1_aadhar ?? '-'),
            $insurance->child2_name ?? '-',
            $insurance->child2_dob ? date('d-M-Y', strtotime($insurance->child2_dob)) : '-',
           " " . ($insurance->child2_aadhar ?? '-'),
            $insurance->permanent_address ?? '-',
            $insurance->communication_address ?? '-',
            $insurance->father_name ?? '-',
            ($insurance->nominee_name ?? '-') . 
            ($insurance->nominee_relation ? ' (' . $insurance->nominee_relation . ')' : '')
        ];
    }
    
    public function columnFormats(): array
{
    return [
        'D' => NumberFormat::FORMAT_TEXT, // Employee Aadhar
        'M' => NumberFormat::FORMAT_TEXT, // Spouse Aadhar
        'P' => NumberFormat::FORMAT_TEXT, // Child 1 Aadhar
        'S' => NumberFormat::FORMAT_TEXT, // Child 2 Aadhar
    ];
}

  public function styles(Worksheet $sheet)
{
    $sheet->getStyle('A1:W1')->getFont()->setBold(true);

    $sheet->getStyle('A1:W1')->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

    return [];
}

public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {

            $sheet = $event->sheet->getDelegate();

            $lastRow = $sheet->getHighestRow();
            $lastColumn = 'W'; 

            $fullRange = "A1:{$lastColumn}{$lastRow}";

            $sheet->getStyle('A1:W1')
                ->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setARGB('FFFFCC99');

            $sheet->getRowDimension(1)->setRowHeight(40);

            $sheet->getStyle('A1:W1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $sheet->getStyle("A2:{$lastColumn}{$lastRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $sheet->getStyle('A1:W1')->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
        }
    ];
}

 
}