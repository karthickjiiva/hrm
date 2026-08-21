<?php
namespace App\Exports;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Holiday;
use App\Models\Leave;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\PageMargins;

class SettlementReportExport implements WithEvents
{
    // Added $fromDate to the protected properties
    protected $user, $fromDate, $relievingDate, $leaveDays, $leaveAmount, $pf, $profTax, $siteAdvance, $others;

  public function __construct($user, $fromDate, $relievingDate, array $data = [])
{
    $this->user = $user;
    $this->fromDate = Carbon::parse($fromDate);
    $this->relievingDate = Carbon::parse($relievingDate);
    $this->leaveDays = $data['leaveDays'] ?? 0;
    $this->leaveAmount = $data['leaveAmount'] ?? 0;
    $this->pf = $data['pf'] ?? 0;
    $this->profTax = $data['profTax'] ?? 0;
    $this->siteAdvance = $data['siteAdvance'] ?? 0;
    $this->others = $data['others'] ?? 0;
}

protected function isNonWorkingDay($date)
{
    return $date->dayOfWeek == Carbon::SUNDAY || $this->isHoliday($date);
}

/**
 * leaves: array of objects/rows {leave_date, is_half_day, half_day_type}
 * startDate, endDate: Carbon|string
 */
protected function calculateExtendedLossOfPay($leaves, $startDate, $endDate)
{
    $leaveMap = [];
    foreach ($leaves as $leave) {
        $leaveMap[Carbon::parse($leave->leave_date)->toDateString()] = [
            'is_half_day' => $leave->is_half_day,
            'half_day_type' => $leave->half_day_type ?? null
        ];
    }

    // Step 1: Build full dateMap for the period
    $dateMap = [];
    $current = Carbon::parse($startDate)->copy();
    $end = Carbon::parse($endDate);
    while ($current->lte($end)) {
        $str = $current->toDateString();
        $dateMap[$str] = [
            'is_holiday' => $this->isNonWorkingDay($current),
            'is_leave'   => isset($leaveMap[$str]),
            'is_half_day'=> $leaveMap[$str]['is_half_day'] ?? false,
            'half_day_type' => $leaveMap[$str]['half_day_type'] ?? null
        ];
        $current->addDay();
    }

    $lossOfPay = 0;

    $dates = array_keys($dateMap);
    $total = count($dates);

    for ($i = 0; $i < $total; $i++) {
        $today = $dates[$i];
        $info = $dateMap[$today];

        if ($info['is_leave']) {
            if ($info['is_half_day']) {
                // Morning half: always LOP
                if ($info['half_day_type'] == 'morning') {
                    $lossOfPay += 0.5;
                }
                // Evening half: LOP if next working day also leave (see below)
                else if ($info['half_day_type'] == 'evening') {
                    // Find the next working day (skip holiday/sundays)
                    $j = $i + 1;
                    while ($j < $total && $dateMap[$dates[$j]]['is_holiday']) { $j++; }
                    if ($j < $total && $dateMap[$dates[$j]]['is_leave']) {
                        $lossOfPay += 0.5;
                    }
                }
            } else {
                // Full day leave
                $lossOfPay += 1.0;
            }
        }

        // Now, check if today is a holiday and should be sandwiched count for LOP
        if ($info['is_holiday']) {
            // Find previous working day with leave
            $k = $i - 1;
            while ($k >= 0 && $dateMap[$dates[$k]]['is_holiday']) $k--;
            // Find next working day with leave
            $j = $i + 1;
            while ($j < $total && $dateMap[$dates[$j]]['is_holiday']) $j++;

            $hasLeavePrev = $k >= 0 && $dateMap[$dates[$k]]['is_leave'] &&
                    (
                        !$dateMap[$dates[$k]]['is_half_day'] || // full day
                        $dateMap[$dates[$k]]['half_day_type'] == 'evening' // evening half
                    );
            $hasLeaveNext = $j < $total && $dateMap[$dates[$j]]['is_leave'] &&
                    (
                        !$dateMap[$dates[$j]]['is_half_day'] || // full day
                        $dateMap[$dates[$j]]['half_day_type'] == 'morning' // morning half
                    );
            if ($hasLeavePrev && $hasLeaveNext) {
                $lossOfPay += 1.0;
                $dateMap[$today]['holiday_LOP_counted'] = true; // So we don't count again if multiple passes
            }
        }
    }

    return $lossOfPay;
}

protected function isHoliday(Carbon $date)
{
    // Implement your holiday checking
    return Holiday::whereDate('date', $date)->exists();
}

protected function amountToWords($number)
{
    // Use en_IN for Lakhs/Crores instead of Thousands/Millions
    $formatter = new \NumberFormatter("en_IN", \NumberFormatter::SPELLOUT);
    
    // Some PHP versions still output "thousand" for lakhs in en_IN 
    // depending on the ICU library version. If it does, we capitalize it.
    $words = $formatter->format(round($number));
    
    return strtoupper($words) . " ONLY";
}

    public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            /** * ==========================================================
             * YOUR PERFECT LAYOUT (UNTOUCHED)
             * ==========================================================
             */
            $sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
                ->setPaperSize(PageSetup::PAPERSIZE_A4);
            $sheet->setShowGridlines(false);
            $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

            foreach ([
                'A' => 27,
                'B' => 12,
                'C' => 12,
                'D' => 28,
                'E' => 18,
                'F' => 14
            ] as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }

            $sheet->mergeCells('A2:F2');
            $sheet->setCellValue('A2', 'EMPLOYEE FULL AND FINAL SETTLEMENT');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(2)->setRowHeight(32);

            // DATE
            $sheet->setCellValue('A3', 'DATE :');
            $sheet->setCellValue('B3', now()->format('d M Y'));

            // EMPLOYEE DETAILS
            $sheet->setCellValue('A5', 'NAME OF THE EMPLOYEE :');
            $sheet->setCellValue('B5', strtoupper($this->user->name));

            $sheet->setCellValue('A6', 'DESIGNATION :');
            $sheet->setCellValue('B6', $this->user->designation?->name ?? '');

            $sheet->setCellValue('A7', 'DEPARTMENT :');
            $sheet->setCellValue('B7', $this->user->department?->name ?? '');

            $sheet->setCellValue('A8', 'EMPLOYEE NO :');
            $sheet->setCellValue('B8', $this->user->employee_number);

            $joining = Carbon::parse($this->user->joining_date);
            $service = $joining->diff($this->relievingDate);
            $sheet->setCellValue('A9', 'TOTAL SERVICE :');
            $sheet->setCellValue('B9', "{$service->y} Years, {$service->m} Months");

            $sheet->setCellValue('E3', 'DATE OF JOINING :');
            $sheet->setCellValue('F3', $joining->format('d-M-y'));
            $sheet->setCellValue('E4', 'DATE OF CONFIRM :');
            $sheet->setCellValue('F4', $joining->format('d-M-y'));
            $sheet->setCellValue('E5', 'DATE OF RELIEVING :');
            $sheet->setCellValue('F5', $this->relievingDate->format('d-M-y'));
            $sheet->setCellValue('E7', 'BASIC :');
            $sheet->setCellValue('F7', $this->user->basic_salary);
            $sheet->setCellValue('E8', 'HRA :');
            $sheet->setCellValue('F8', $this->user->hra);
            $sheet->setCellValue('E9', 'FOOD ALLOW :');
            $sheet->setCellValue('F9', $this->user->food_allow ?? 0);
            $sheet->setCellValue('E10', 'CONVEYANCE :');
            $sheet->setCellValue('F10', $this->user->conveyance ?? 0);
            $sheet->setCellValue('E11', 'GROSS :');
            $sheet->setCellValue('F11', $this->user->monthly_amount);

            $sheet->getStyle('F3:F11')
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $sheet->getStyle('A3:A9')->getFont()->setBold(true);
            $sheet->getStyle('E3:E11')->getFont()->setBold(true);
            $sheet->getStyle('A3:B9')->getFont()->setSize(10.5);
            $sheet->getStyle('E3:F11')->getFont()->setSize(10.5);

            $sheet->setCellValue('A13', 'EARNINGS');
            $sheet->setCellValue('B13', 'NO OF DAYS');
            $sheet->setCellValue('C13', 'AMOUNT');
            $sheet->setCellValue('D13', 'DEDUCTIONS');
            $sheet->setCellValue('E13', 'NO OF DAYS');
            $sheet->setCellValue('F13', 'AMOUNT');

            $sheet->getStyle('A13:F13')->getFont()->setBold(true);
            $sheet->getStyle('A13:F13')
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getRowDimension(13)->setRowHeight(22);

            /** * ==========================================================
             * DYNAMIC DATA LOOP (REPLACED STATIC ROWS)
             * ==========================================================
             */
            $currentRow = 14;
            $period = CarbonPeriod::create($this->fromDate, '1 month', $this->relievingDate);
            $totalEarnings = 0;
            $totalDeductions = 0;

            foreach ($period as $dt) {
                $monthStart = $dt->copy()->startOfMonth();
                $monthEnd = $dt->copy()->endOfMonth();

                // 1. Determine overlap
                $actualStart = $this->fromDate->greaterThan($monthStart) ? $this->fromDate : $monthStart;
                $actualEnd = $this->relievingDate->lessThan($monthEnd) ? $this->relievingDate : $monthEnd;

                // 2. Calculate Gross Days
                $grossDays = $actualStart->diff($actualEnd)->days + 1;

                // 3. Fetch leaves
                $leaves = \App\Models\Leave::where('user_id', $this->user->id)
                    ->whereBetween('leave_date', [$actualStart, $actualEnd])
                    ->get();

                // 4. Calculate Loss of Pay
                $lopDays = $this->calculateExtendedLossOfPay($leaves, $actualStart, $actualEnd);

                // 5. Final Payable Days
                $payableDays = $grossDays - $lopDays;

                // 6. Salary & PF Calculation
                $monthlyAmount = ($this->user->monthly_amount / $dt->daysInMonth) * $payableDays;
                $monthlyPf = ($this->user->monthly_pf / $dt->daysInMonth) * $payableDays;

                $totalEarnings += round($monthlyAmount);
                $totalDeductions += round($monthlyPf);

                // 7. Set the Values
                $sheet->setCellValue("A$currentRow", 'SALARY - ' . strtoupper($dt->format('M Y')));
                $sheet->setCellValue("B$currentRow", $payableDays);
                $sheet->setCellValue("C$currentRow", round($monthlyAmount));

                $sheet->setCellValue("D$currentRow", 'PF CONTRIBUTION ' . strtoupper($dt->format('M Y')));
                $sheet->setCellValue("E$currentRow", $payableDays);
                $sheet->setCellValue("F$currentRow", round($monthlyPf));

                $currentRow++;
            }

            $lastDataRow = $currentRow - 1;
            $sheet->getStyle("A14:F$lastDataRow")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $leaveMaster = \App\Models\EmployeeLeaveMaster::where('employee_id', $this->user->id)->first();
            $elBalance = $leaveMaster ? $leaveMaster->el : 0;

            // 2. Calculate Leave Salary Amount
            $leaveSalaryAmount = ($this->user->monthly_amount / 31) * $elBalance;

            // 3. Set Leave Salary Row
            $sheet->setCellValue("A$currentRow", 'LEAVE SALARY');
            $sheet->setCellValue("B$currentRow", $elBalance);
            $sheet->setCellValue("C$currentRow", round($leaveSalaryAmount));

            // Move pointer forward
            $currentRow++;

            // 4. Set Values for Site Advance
            $sheet->setCellValue("A$currentRow", "SITE ADVANCE PAYABLE BY BCIPL");
            $sheet->setCellValue("B$currentRow", "0");
            $sheet->setCellValue("C$currentRow", $this->siteAdvance);

            $slotStart = ($this->fromDate->month >= 4 && $this->fromDate->month <= 9)
                ? $this->fromDate->copy()->month(4)->startOfMonth()
                : ($this->fromDate->month >= 10 ? $this->fromDate->copy()->month(10)->startOfMonth() : $this->fromDate->copy()->subYear()->month(10)->startOfMonth());

            $slotEnd = ($this->relievingDate->month >= 4 && $this->relievingDate->month <= 9)
                ? $this->relievingDate->copy()->month(9)->endOfMonth()
                : ($this->relievingDate->month >= 10 ? $this->relievingDate->copy()->addYear()->month(3)->endOfMonth() : $this->relievingDate->copy()->month(3)->endOfMonth());

            $monthsCount = $this->fromDate->copy()->startOfMonth()->diffInMonths($slotEnd->copy()->startOfMonth()) + 1;
            $totalProfTax = ($this->user->monthly_prof_tax ?? 0) * $monthsCount;

            // PROFESSIONAL TAX
            $sheet->setCellValue("D$currentRow", 'PROFESSIONAL TAX');
            $sheet->setCellValue("E$currentRow", $this->fromDate->format('M y') . ' - ' . $slotEnd->format('M y'));
            $sheet->setCellValue("F$currentRow", $totalProfTax);

            // OTHERS (earnings side) + TDS (deductions side)
            $currentRow++;
            $sheet->setCellValue("A$currentRow", 'OTHERS');
            $sheet->setCellValue("B$currentRow", "0");
            $sheet->setCellValue("C$currentRow", $this->others);
            $sheet->setCellValue("D$currentRow", 'TDS');
            $sheet->setCellValue("E$currentRow", '');
            $sheet->setCellValue("F$currentRow", 0);

            // FOOD COUPON
            $currentRow++;
            foreach (['A', 'B', 'C'] as $col) {
                $sheet->setCellValue($col . $currentRow, '');
            }
            $sheet->setCellValue("D$currentRow", 'FOOD COUPON');
            $sheet->setCellValue("E$currentRow", '');
            $sheet->setCellValue("F$currentRow", 0);

            $sheet->getStyle("A14:F$currentRow")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);

            // Row Heights for Data Area
            for ($i = 14; $i <= $currentRow; $i++) {
                $sheet->getRowDimension($i)->setRowHeight(22);
            }

            /** * ==========================================================
             * TOTALS & FOOTER (UNTOUCHED LOGIC)
             * ==========================================================
             */
            $summaryRow = $currentRow + 1;

            $sheet->getStyle("A13:F$summaryRow")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            // Total - (A) Row
            $sheet->setCellValue("A$summaryRow", 'Total - (A)');
            $sheet->setCellValue("C$summaryRow", "=SUM(C14:C$currentRow)");

            // Total - (B) Row
            $sheet->setCellValue("D$summaryRow", '(B)');
            $sheet->setCellValue("F$summaryRow", "=SUM(F14:F$currentRow)");

            $sheet->getStyle("C$summaryRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);
            $sheet->getStyle("F$summaryRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THICK);

            // Spacer Row
            $spacerRow = $summaryRow + 1;
            $sheet->mergeCells("A$spacerRow:F$spacerRow");
            $sheet->getStyle("A$spacerRow:F$spacerRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

            // Settlement Calculation Row
            $settleRow = $spacerRow + 1;
            $sheet->setCellValue("A$settleRow", '(C) BONUS DUE : RS.');
            $sheet->setCellValue("B$settleRow", '0.00');
            $sheet->setCellValue("D$settleRow", 'SETTLEMENT :(A+C-B)');
            $sheet->setCellValue("F$settleRow", "=C$summaryRow-F$summaryRow");
            $sheet->getStyle("F$settleRow")->getFont()->setBold(true);

            $sheet->getStyle("A$settleRow:F$settleRow")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A$settleRow:F$settleRow")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            // Final Amount Payable
            $payableRow = $settleRow + 2;
            $sheet->setCellValue("A$payableRow", 'TOTAL AMOUNT PAYABLE BY EMPLOYER :');
            $sheet->setCellValue("C$payableRow", "=F$settleRow");

            // Word Conversion Data
            $totalEarnings += round($leaveSalaryAmount);
            $totalEarnings += $this->siteAdvance;
            $totalEarnings += $this->others;
            $totalDeductions += $totalProfTax;

            $netFinal = $totalEarnings - $totalDeductions;
            $words = $this->amountToWords($netFinal);

            $wordsRow = $payableRow + 2;
            $sheet->setCellValue("A$wordsRow", 'AMOUNT IN WORDS :');
            $sheet->setCellValue("B$wordsRow", $words);
            $sheet->getStyle("B$wordsRow")->getFont()->setBold(true);

            $noteRow = $wordsRow + 2;
            $sheet->setCellValue("A$noteRow", 'NOTE :');
            $sheet->getStyle("A$noteRow:F$noteRow")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            // Signatures Header
            $sigHeaderRow = $noteRow + 3;
            $sheet->setCellValue("A$sigHeaderRow", 'HR');
            $sheet->setCellValue("B$sigHeaderRow", 'CHECKED');
            $sheet->setCellValue("C$sigHeaderRow", 'AVP');
            $sheet->setCellValue("D$sigHeaderRow", 'CFO');
            $sheet->setCellValue("E$sigHeaderRow", 'HOD');
            $sheet->setCellValue("F$sigHeaderRow", 'DIRECTOR');

            // Acceptance Text
            $acceptRow = $sigHeaderRow + 2;
            $sheet->setCellValue("A$acceptRow", 'I ACCEPT THE ABOVE FINAL SETTLEMENT.');

            // Date & Signature Row
            $dateSignRow = $acceptRow + 2;
            $sheet->setCellValue("A$dateSignRow", 'DATE :');
            $sheet->setCellValue("E$dateSignRow", 'SIGNATURE OF THE EMPLOYEE');

            // Lines for Signatures
            $sheet->getStyle("A$sigHeaderRow:F$sigHeaderRow")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
            $sheet->getStyle("A$dateSignRow:F$dateSignRow")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            // Final alignment for amount columns
            $sheet->getStyle("C14:C$payableRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("F14:F$settleRow")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $finalRow = $dateSignRow;
            $sheet->getStyle("A1:F$finalRow")->getFill()->setFillType('solid')->getStartColor()->setRGB('FFFFFF');
            $sheet->getStyle("A1:F$finalRow")->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN);

            // Page Setup
            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()->setFitToHeight(1);
            $sheet->getPageSetup()->setFitToPage(true);
            $sheet->getPageSetup()->setPrintArea("A1:F$finalRow");

            // Margins
            $sheet->getPageMargins()->setTop(0.4);
            $sheet->getPageMargins()->setBottom(0.4);
            $sheet->getPageMargins()->setLeft(0.3);
            $sheet->getPageMargins()->setRight(0.3);
        }
    ];
}
}
?>