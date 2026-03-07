<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\PayrollNew\IndexRequest;
use Examyou\RestAPI\ApiResponse;
use App\Models\PayrollNew;
use App\Models\LeaveAdjustment;
use App\Models\EmployeeLoan;
use App\Models\EmpAdvance;
use App\Models\MonthlyLeaveSummary;
use App\Models\EmployeeLoanRepayment;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\BankStatement;
use App\Models\Company;
use App\Models\StaffMember;
use Barryvdh\DomPDF\Facade\Pdf; 
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;

class MyPayrollController extends ApiBaseController
{
    protected $model = PayrollNew::class;


    public function generatePayroll(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        $employees = StaffMember::where('status', 'active')
        ->where('has_resigned', 0) 
        ->where('hold_status', 0) 
        ->where('name', '!=', 'Admin')
        ->get();

        $generated = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $payroll = $this->processEmployeePayroll($employee, $month, $year);
                $generated[] = $payroll->id;
            } catch (\Exception $e) {
                $errors[] = [
                    'employee_id' => $employee->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $this->makeSuccessResponse('Payroll processed successfully.', [
            'generated_count' => count($generated),
            'error_count' => count($errors),
            'errors' => $errors
        ]);
    }

    protected function makeSuccessResponse($message, $data = [])
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }


    protected function processEmployeePayroll($employee, $month, $year)
    {
        $totalDaysInMonth = Carbon::create($year, $month)->daysInMonth;

        $leaveMasterBefore = \App\Models\EmployeeLeaveMaster::firstOrNew(['employee_id' => $employee->id]);
        $openingCL = (float) ($leaveMasterBefore->cl ?? 0);
        $openingSL = (float) ($leaveMasterBefore->sl ?? 0);
        $openingEL = (float) ($leaveMasterBefore->el ?? 0);

        $leaveInfo = $this->getDetailedLeaveInfo($employee->id, $month, $year);
        $totalLeavesTaken = round(array_sum($leaveInfo['all_leave_dates']), 2);
        $daysPresent = $totalDaysInMonth - $totalLeavesTaken;
        
        $employeeType = $employee->employeeType->type ?? '';
        $earned = ['cl' => 0, 'sl' => 0, 'el' => 0];
    
        if ($employeeType !== 'Consultant Emp') {
            $earned = $this->calculateMonthlyEarnedLeaves($employee, $daysPresent, $month, $year);
            $leaveMaster = $this->updateEarnedLeaves($employee->id, $earned);
        } else {
            $leaveMaster = \App\Models\EmployeeLeaveMaster::firstOrNew(['employee_id' => $employee->id]);
        }

        // $earned = $this->calculateMonthlyEarnedLeaves($employee, $daysPresent, $month, $year);
        // $earned = $this->calculateMonthlyEarnedLeaves($daysPresent);
        // $leaveMaster = $this->updateEarnedLeaves($employee->id, $earned);

        $leaveDeduction = $this->applyLeaveDeductions($employee->id, $totalLeavesTaken, $leaveMaster);
        $lossOfPayDays = $leaveDeduction['loss_of_pay_days'];
        $ded = $leaveDeduction['deductions'];
        $payableDays = $totalDaysInMonth - $lossOfPayDays;

        $monthlyBasic = (float) $employee->basic_salary;
        $monthlyHRA = (float) $employee->monthly_hra_percent_monthly;
        $monthlyAllowance = (float) $employee->monthly_allowance_percent;
        $monthlyFood = (float) $employee->monthly_food_allowance_percent;

        $perDaySalary = ($monthlyBasic + $monthlyHRA + $monthlyAllowance + $monthlyFood) / $totalDaysInMonth;

        $basic = round(($monthlyBasic / $totalDaysInMonth) * $payableDays, 2);
        $hra = round(($monthlyHRA / $totalDaysInMonth) * $payableDays, 2);
        $allowance = round(($monthlyAllowance / $totalDaysInMonth) * $payableDays, 2);
        $foodAllowance = round(($monthlyFood / $totalDaysInMonth) * $payableDays, 2);

        $totalEarnings = $basic + $hra + $allowance + $foodAllowance;

        $pf = 0;
        if ($employee->pf_enabled) {
            $calculatedPF = round(($basic * $employee->pf_percentage) / 100, 2);
            $pf = ((float) $employee->monthly_pf == $employee->pf_percentage) ? $employee->monthly_pf : $calculatedPF;
        }

        $esi = 0;
        if ($employee->esi_enabled) {
            $esiBase = $basic + $hra;
            $calculatedESI = round(($esiBase * $employee->esi_percentage) / 100, 2);
            //$calculatedESI = round(($basic * $employee->esi_percentage) / 100, 2);
            $esi = ((float) $employee->monthly_esi == $employee->esi_percentage) ? $employee->monthly_esi : $calculatedESI;
        }

        $pt = 0;
        if ($employee->prof_tax_enabled) {
            $calculatedPT = round(($basic * $employee->prof_tax_percentage) / 100, 2);
            $pt = ((float) $employee->monthly_prof_tax == $employee->prof_tax_percentage) ? $employee->monthly_prof_tax : $calculatedPT;
        }

        $tds = 0;
        if ($employee->tds_enabled) {
            $calculatedTDS = round(($basic * $employee->tds_percentage) / 100, 2);
            $tds = ((float) $employee->monthly_tds == $employee->tds_percentage) ? $employee->monthly_tds : $calculatedTDS;
        }

        $netSalary = round($totalEarnings - ($pf + $esi + $pt + $tds), 2);

        $monthString = sprintf('%04d-%02d', $year, $month); 

        $advanceDeduction = EmpAdvance::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->where('deduct_month', $monthString)
            ->first();      

        if ($advanceDeduction) {
            $advanceDeduction->status = 'paid';
            $advanceDeduction->save();
        }            

        $advanceAmount = $advanceDeduction ? (float)$advanceDeduction->amount : 0;

        $loanDeductionAmount = 0;
        $activeLoans = EmployeeLoan::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->get();

        foreach ($activeLoans as $loan) {
        $repayment = $loan->repayments()
            ->whereYear('repayment_month', $year)
            ->whereMonth('repayment_month', $month)
            ->where('status', 'pending')
            ->first();

            if ($repayment) {
                $loanDeductionAmount += (float) $repayment->amount;
                $repayment->status = 'paid';
                $repayment->remarks = 'Deducted from salary';
                $repayment->save();
                $pending = $loan->repayments()->where('status', 'pending')->count();
                if ($pending === 0) {
                    $loan->status = 'closed';
                    $loan->save();
                }
            }
        }

        $totalLoanAndAdvance = round($loanDeductionAmount + $advanceAmount, 2);
        $netSalary -= $totalLoanAndAdvance;
        $netSalary = round($netSalary, 2);

        $payrollData = [
        'total_working_days' => $totalDaysInMonth,
        'loss_of_pay_days' => $lossOfPayDays,
        'days_payable' => $payableDays, 
        'actual_payable_days' => $payableDays,     
        'per_day_salary' => round($perDaySalary, 2),
        'basic' => $basic,
        'hra' => $hra,
        'allowance' => $allowance,
        'food_allowance' => $foodAllowance,
        'total_earnings' => $totalEarnings,
        'pf_employee' => $pf,
        'esi_employee' => $esi,
        'professional_tax' => $pt,
        'tds' => $tds,
        'total_contributions' => $pf + $esi,
        'total_taxes_deductions' => $pt + $tds,
        'net_salary' => $netSalary,
        'loan_deduct' => round($loanDeductionAmount, 2), 
        'advance_deduct' => round($advanceAmount, 2)
    ];

    $payroll = PayrollNew::updateOrCreate(
        [
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year,
        ],
        $payrollData
    );

    BankStatement::updateOrCreate(
        [
            'employee_id' => $employee->id,
            'month' => $month,
            'year' => $year,
        ],
        [
            'remark' => 'Salary for ' . Carbon::create()->month($month)->format('F'),
            'amount' => $netSalary,
        ]
    );

     $leaveMasterAfter = \App\Models\EmployeeLeaveMaster::firstOrNew(['employee_id' => $employee->id]);
        $closingCL = (float) ($leaveMasterAfter->cl ?? 0);
        $closingSL = (float) ($leaveMasterAfter->sl ?? 0);
        $closingEL = (float) ($leaveMasterAfter->el ?? 0);

        $summaryPayload = [
            'opening_cl' => round($openingCL, 2),
            'opening_sl' => round($openingSL, 2),
            'opening_el' => round($openingEL, 2),

            'earned_cl'  => (float) $earned['cl'],
            'earned_sl'  => (float) $earned['sl'],
            'earned_el'  => (float) $earned['el'],

            'availed_cl' => round($ded['cl'] ?? 0, 2),
            'availed_sl' => round($ded['sl'] ?? 0, 2),
            'availed_el' => round($ded['el'] ?? 0, 2),

            'closing_cl' => round($closingCL, 2),
            'closing_sl' => round($closingSL, 2),
            'closing_el' => round($closingEL, 2),

            'total_availed' => round(($ded['cl'] ?? 0) + ($ded['sl'] ?? 0) + ($ded['el'] ?? 0), 2),
            'closing_balance_total' => round($closingCL + $closingSL + $closingEL, 2),
            'lop_days' => round($lossOfPayDays, 2),

            'sandwich_days' => count($leaveInfo['sandwich']),
            'half_days' => collect($leaveInfo['all_leave_dates'])->filter(fn($v) => $v == 0.5)->count(),

            'total_working_days' => $totalDaysInMonth,
            'days_payable' => $payableDays,
            'loss_of_pay_days' => $lossOfPayDays,
        ];

        MonthlyLeaveSummary::updateOrCreate(
            ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
            $summaryPayload
        );

    return $payroll;

    }

protected function getDetailedLeaveInfo($employeeId, $month, $year)
{
    $start = Carbon::create($year, $month, 1)->startOfMonth();
    $end = Carbon::create($year, $month, 1)->endOfMonth();

    $leaves = Leave::where('user_id', $employeeId)
        ->whereBetween('leave_date', [$start, $end])
        ->get();

    $leaveDates = [];
    foreach ($leaves as $leave) {
        $dateStr = Carbon::parse($leave->leave_date)->toDateString();
        $value = ($leave->is_half_day) ? 0.5 : 1.0;
        if (!isset($leaveDates[$dateStr]) || $value > $leaveDates[$dateStr]) {
            $leaveDates[$dateStr] = (float) $value;
        }
    }

    $sandwich = $this->identifySandwichLeaves($employeeId, $start, $end);
    foreach ($sandwich as $sandDate) {
        if (!isset($leaveDates[$sandDate])) {
            $leaveDates[$sandDate] = 1.0;
        } elseif ($leaveDates[$sandDate] < 1.0) {
            $leaveDates[$sandDate] = 1.0;
        }
    }
    return [
        'all_leave_dates' => $leaveDates,
        'sandwich' => $sandwich,
        'original' => $leaves
    ];
}

    protected function calculateMonthlyEarnedLeaves($employee, $presentDays, $month, $year)
    {
        $joiningDate = Carbon::parse($employee->joining_date);
        $payrollDate = Carbon::create($year, $month, 1)->endOfMonth();
        $gapInYears = $joiningDate->diffInYears($payrollDate);

        $earned = ['cl' => 0, 'sl' => 0, 'el' => 0];

        if ($gapInYears >= 1) {
           if ($presentDays >= 4.5 && $presentDays <= 8) {
                $earned['cl'] = 0.5;
            } elseif ($presentDays >= 8.5 && $presentDays <= 12) {
                $earned['cl'] = 1;
            } elseif ($presentDays >= 12.5 && $presentDays <= 16) {
                $earned['cl'] = 1;
                $earned['sl'] = 0.5;
            } elseif ($presentDays >= 16.5 && $presentDays <= 21) {
                $earned['cl'] = 1;
                $earned['sl'] = 1;
            } elseif ($presentDays >= 21.5 && $presentDays <= 24) {
                $earned['cl'] = 1;
                $earned['sl'] = 1;
                $earned['el'] = 0.5;
            } elseif ($presentDays >= 24.5) {
                $earned['cl'] = 1;
                $earned['sl'] = 1;
                $earned['el'] = 1;
            }
        } else {
            if ($presentDays >= 8 && $presentDays <= 13) {
                $earned['cl'] = 0.5;
            } elseif ($presentDays >= 13.5 && $presentDays <= 18) {
                $earned['cl'] = 1;
            } elseif ($presentDays >= 18.5 && $presentDays <= 24) {
                $earned['cl'] = 1;
                $earned['sl'] = 0.5;
            } elseif ($presentDays >= 24.5) {
                $earned['cl'] = 1;
                $earned['sl'] = 1;
            }
        }
        return $earned; 
    }

    protected function calculateMonthlyEarnedLeaves_old($presentDays)
    {
        if ($presentDays > 26) {
            return ['cl' => 1, 'sl' => 1, 'el' => 1];
        } elseif ($presentDays > 16) {
            return ['cl' => 1, 'sl' => 1, 'el' => 0];
        } elseif ($presentDays > 8) {
            return ['cl' => 1, 'sl' => 0, 'el' => 0];
        }
        return ['cl' => 0, 'sl' => 0, 'el' => 0];
    }


    protected function updateEarnedLeaves($employeeId, $earned)
    {
        $employee = StaffMember::find($employeeId);
        $employeeType = $employee->employeeType->type ?? '';
        if ($employeeType === 'Consultant Emp') {
            return \App\Models\EmployeeLeaveMaster::firstOrNew(['employee_id' => $employeeId]);
        }
    
        $record = \App\Models\EmployeeLeaveMaster::firstOrNew(['employee_id' => $employeeId]);
        $record->cl += $earned['cl'];
        $record->sl += $earned['sl'];
        $record->el += $earned['el'];
        $record->save();
        return $record;
    }

    protected function updateEarnedLeaves_oldd($employeeId, $earned)
    {
        $record = \App\Models\EmployeeLeaveMaster::firstOrNew(['employee_id' => $employeeId]);
        $record->cl += $earned['cl'];
        $record->sl += $earned['sl'];
        $record->el += $earned['el'];
        $record->save();
        return $record;
    }

    protected function applyLeaveDeductions($employeeId, $totalLeaves, $leaveRecord)
    {
        $remaining = (float) $totalLeaves;
        $deductions = ['cl' => 0.0, 'sl' => 0.0, 'el' => 0.0];
        $leaveOrder = ['cl', 'sl', 'el'];
    
        foreach ($leaveOrder as $type) {
            if ($remaining <= 0) break;
    
            $available = (float) $leaveRecord->$type;
    
            if ($available > 0) {
                $used = min($available, $remaining);
                $deductions[$type] += $used;
                $leaveRecord->$type = round($available - $used, 2);
                $remaining = round($remaining - $used, 2);
            }
        }
        $leaveRecord->save();
        return [
            'deductions' => $deductions,
            'loss_of_pay_days' => max(0, $remaining)
        ];
    }


protected function identifySandwichLeaves($employeeId, Carbon $startDate, Carbon $endDate)
{
    $leaves = Leave::where('user_id', $employeeId)
        ->whereBetween('leave_date', [$startDate, $endDate])
        ->get()
        ->groupBy(fn($leave) => Carbon::parse($leave->leave_date)->toDateString());

    $dateMap = [];
    $current = $startDate->copy();
    while ($current->lte($endDate)) {
        $dateStr = $current->toDateString();

        $isHoliday = $this->isNonWorkingDay($current);
        $isLeave = $leaves->has($dateStr);
        $isHalfDay = $isLeave && $leaves[$dateStr]->first()->is_half_day;
        $halfDayType = $isHalfDay ? $leaves[$dateStr]->first()->half_day_type : null;

        $dateMap[$dateStr] = [
            'is_holiday' => $isHoliday,
            'is_leave' => $isLeave,
            'is_half_day' => $isHalfDay,
            'half_day_type' => $halfDayType
        ];

        $current->addDay();
    }

    $sandwichLeaves = [];
    $dates = array_keys($dateMap);
    $total = count($dates);

    for ($i = 0; $i < $total; $i++) {
        $today = $dates[$i];
        $info = $dateMap[$today];

        if (!$info['is_holiday']) continue;
        $k = $i - 1;
        while ($k >= 0 && $dateMap[$dates[$k]]['is_holiday']) {
            $k--;
        }

        $j = $i + 1;
        while ($j < $total && $dateMap[$dates[$j]]['is_holiday']) {
            $j++;
        }

        if ($k >= 0 && $j < $total) {
            $prev = $dateMap[$dates[$k]];
            $next = $dateMap[$dates[$j]];

            $hasLeavePrev = $prev['is_leave'];
            $hasLeaveNext = $next['is_leave'];

            if ($hasLeavePrev && $hasLeaveNext) {
                $sandwichLeaves[] = $today;
            }
        }
    }

    return $sandwichLeaves;
}


    protected function isNonWorkingDay($date)
    {
        return $date->dayOfWeek == Carbon::SUNDAY || $this->isHoliday($date);
    }

    protected function isHoliday(Carbon $date)
    {
        return Holiday::whereDate('date', $date)->exists();
    }

}
