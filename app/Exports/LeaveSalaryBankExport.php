<?php
namespace App\Exports;

use App\Models\LeaveSalaryStatement;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithColumnFormatting};
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LeaveSalaryBankExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    protected $year;

    public function __construct($year) {
        $this->year = $year;
    }

    public function collection() {
        return LeaveSalaryStatement::with(['employee.bankMaster'])
            ->where('year', $this->year)
            ->get();
    }

public function headings(): array
{
    return [
        "Transaction type\n(Within Bank (WIB)/\nNEFT (NFT)/\nRTGS (RTG)/\nIMPS (IFC))",
        "Amount (₹)\n(Should not be more than 15 digit including decimals and paise)",
        "Debit Account no\n(Should be exactly 12 digit)",
        "IFSC\n(Always 11 character alphanumeric and 5th character always 0 (zero))\n(For ICICI bank accounts keep it blank)",
        "Beneficiary Account No\n(Max length for other bank 34 character alphanumeric and for ICICI Bank 12 digit number)",
        "Beneficiary Name\n(Max length 32 Character)\n(No Special Character is allowed but Space is allowed)",
        "Remarks for Client\n(should not be more than 21 characters)",
        "Remarks for Beneficiary\n(should not be more than 30 characters)",
    ];
}

    // This forces Excel to treat the Account Number column (E) as Text
    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT, // Column E is 'benef acc no'
        ];
    }

    public function map($row): array {
        $remark = "LEAVESALARY" . $this->year;
        return [
            'WIB',
            number_format($row->leave_salary, 2, '.', ''),
            '602651003205',
            $row->employee->bankMaster->ifsc ?? '-',
            " " . ($row->employee->bankMaster->account_number ?? '-'), 
            $row->employee->name ?? '-',
            $remark,
            $remark,
        ];
    }

 public function styles(Worksheet $sheet)
{
    // Force header row height
    $sheet->getRowDimension(1)->setRowHeight(155);

    // Enable wrap text for header
    $sheet->getStyle('A1:H1')->getAlignment()->setWrapText(true);

    // Column widths
    $sheet->getColumnDimension('A')->setWidth(12);
    $sheet->getColumnDimension('B')->setWidth(22);
    $sheet->getColumnDimension('C')->setWidth(23);
    $sheet->getColumnDimension('D')->setWidth(23);
    $sheet->getColumnDimension('E')->setWidth(25);
    $sheet->getColumnDimension('F')->setWidth(30);
    $sheet->getColumnDimension('G')->setWidth(24);
    $sheet->getColumnDimension('H')->setWidth(24);

    // 🔹 DATA ROW HEIGHT (only rows 2+)
    $highestRow = $sheet->getHighestRow();
    for ($row = 2; $row <= $highestRow; $row++) {
        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    return [
        // Header style (unchanged)
        1 => [
            'font' => [
                'bold' => false,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ],

        // 🔹 DATA FONT SIZE (only rows 2+)
        'A2:H' . $highestRow => [
            'font' => [
                'size' => 11, // increased data font
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ],

        // Borders (unchanged)
        'A1:H' . $highestRow => [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ],
    ];
}


}