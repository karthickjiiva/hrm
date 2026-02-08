<?php
namespace App\Exports;

use App\Models\LeaveSalaryStatement;
use Maatwebsite\Excel\Concerns\{FromCollection, WithHeadings, WithMapping, ShouldAutoSize};

class LeaveSalaryBankExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $year;

    public function __construct($year) {
        $this->year = $year;
    }

    public function collection() {
        // We fetch the data we JUST created in the database
        return LeaveSalaryStatement::with(['employee.bankMaster'])
            ->where('year', $this->year)
            ->get();
    }

    public function headings(): array {
        return [
            'txn type', 'Amount', 'debit no', 'ifsc code', 
            'benef acc no', 'Benef name', 'Remarks for client', 'Remarks for benef'
        ];
    }

    public function map($row): array {
        $remark = "LEAVESALARY" . $this->year;
        return [
            'WIB',                                          // txn type
            $row->leave_salary,                             // Amount
            '243424',                                       // debit no (static as per your req)
            $row->employee->bankMaster->ifsc ?? '-',        // ifsc code
            $row->employee->bankMaster->account_number ?? '-', // benef acc no
            $row->employee->name ?? '-',                    // Benef name
            $remark,                                        // Remarks for client
            $remark,                                        // Remarks for benef
        ];
    }
}