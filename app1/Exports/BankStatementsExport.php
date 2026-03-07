<?php
namespace App\Exports;

use App\Models\BankStatement;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class BankStatementsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithMapping, WithEvents
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
        return BankStatement::with(['employee', 'bankMaster'])
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->get();
    }

    public function headings(): array
    {
        return [
            'Bank Name',
            'IFSC Code',
            'MICR Code',
            'Account Number',
            'Employee Name',
            'Amount',
            'Remark',
            'Date',
        ];
    }

    public function map($statement): array
    {
        return [
            optional($statement->bankMaster)->bank_name ?? '-',
            optional($statement->bankMaster)->ifsc ?? '-',
            optional($statement->bankMaster)->micr ?? '-',
            optional($statement->bankMaster)->account_number ?? '-',
            optional($statement->employee)->name ?? '-',
            $statement->amount,
            $statement->remark,
            $statement->created_at ? $statement->created_at->format('d-m-Y') : '-',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

$monthName = \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->format('F');
$title = "Bank Statements Report - {$monthName} {$this->year}";

                $sheet->insertNewRowBefore(1, 1);
                $sheet->mergeCells('A1:H1'); 
                $sheet->setCellValue('A1', $title);

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0'],  
                    ],
                ]);
 
                $sheet->getStyle('A2:H2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    ],
                    'fill' => [
                        'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4F81BD'], 
                    ],
                ]);
            }
        ];
    }
}
