<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\PayrollNew\IndexRequest;
use Examyou\RestAPI\ApiResponse;
use App\Models\PayrollNew;
use App\Models\LeaveAdjustment;
use Barryvdh\DomPDF\Facade\Pdf; 
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Company;
use Carbon\CarbonTimeZone;
use App\Models\Holiday;
use App\Models\Leave;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;


class MyPayrollController extends ApiBaseController
{
    protected $model = PayrollNew::class;


public function generatePayroll(Request $request)
{
    $month = $request->month;
    $year = $request->year;

    $employees = StaffMember::where('status', 'active')->get();
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

    // 1. Get leaves incl. sandwich
    $leaveInfo = $this->getDetailedLeaveInfo($employee->id, $month, $year);

    $totalLeavesTaken = count($leaveInfo['all_leave_dates']);
    $daysPresent = $totalDaysInMonth - $totalLeavesTaken;

    // 2. Update monthly earned leaves
    $earned = $this->calculateMonthlyEarnedLeaves($daysPresent);
    $leaveMaster = $this->updateEarnedLeaves($employee->id, $earned);

    // 3. Deduct from leave balances (SL > CL > EL)
    $leaveDeduction = $this->applyLeaveDeductions($employee->id, $totalLeavesTaken, $leaveMaster);
    $lossOfPayDays = $leaveDeduction['loss_of_pay_days'];
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
        $calculatedESI = round(($basic * $employee->esi_percentage) / 100, 2);
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

    // 6. Store payroll
    $payroll = PayrollNew::create([
        'employee_id' => $employee->id,
        'month' => $month,
        'year' => $year,
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
        'net_salary' => $netSalary
    ]);

    return $payroll;
}

protected function getDetailedLeaveInfo($employeeId, $month, $year)
{
    $start = Carbon::create($year, $month, 1)->startOfMonth();
    $end = Carbon::create($year, $month, 1)->endOfMonth();

    $leaves = Leave::where('user_id', $employeeId)
        ->whereBetween('leave_date', [$start, $end])
        ->pluck('leave_date')
        ->map(fn($date) => Carbon::parse($date)->toDateString())
        ->toArray();

    $sandwich = $this->identifySandwichLeaves($employeeId, $start, $end);

    return [
        'all_leave_dates' => array_unique(array_merge($leaves, $sandwich)),
        'sandwich' => $sandwich,
        'original' => $leaves
    ];
}

protected function calculateMonthlyEarnedLeaves($presentDays)
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
    $record = \App\Models\EmployeeLeaveMaster::firstOrNew(['employee_id' => $employeeId]);
    $record->cl += $earned['cl'];
    $record->sl += $earned['sl'];
    $record->el += $earned['el'];
    $record->save();
    return $record;
}


protected function applyLeaveDeductions($employeeId, $totalLeaves, $leaveRecord)
{
    $remaining = $totalLeaves;
    $deductions = ['sl' => 0, 'cl' => 0, 'el' => 0];

    foreach (['sl', 'cl', 'el'] as $type) {
        $available = $leaveRecord->$type;
        if ($available > 0 && $remaining > 0) {
            $used = min($available, $remaining);
            $deductions[$type] = $used;
            $leaveRecord->$type -= $used;
            $remaining -= $used;
        }
    }

    $leaveRecord->save();

    return [
        'deductions' => $deductions,
        'loss_of_pay_days' => $remaining
    ];
}


protected function identifySandwichLeaves($employeeId, Carbon $startDate, Carbon $endDate)
{
    // Step 1: Fetch all leaves for employee
    $leaves = Leave::where('user_id', $employeeId)
        ->whereBetween('leave_date', [$startDate, $endDate])
        ->get()
        ->groupBy(fn($leave) => Carbon::parse($leave->leave_date)->toDateString());

    // Step 2: Build date map for the full month
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

        if ($info['is_holiday']) {
            // Find previous working day with leave
            $k = $i - 1;
            while ($k >= 0 && $dateMap[$dates[$k]]['is_holiday']) {
                $k--;
            }

            // Find next working day with leave
            $j = $i + 1;
            while ($j < $total && $dateMap[$dates[$j]]['is_holiday']) {
                $j++;
            }

            $hasLeavePrev = false;
            $hasLeaveNext = false;

            if ($k >= 0) {
                $prev = $dateMap[$dates[$k]];
                $hasLeavePrev = $prev['is_leave'] &&
                    (!$prev['is_half_day'] || $prev['half_day_type'] === 'evening');
            }

            if ($j < $total) {
                $next = $dateMap[$dates[$j]];
                $hasLeaveNext = $next['is_leave'] &&
                    (!$next['is_half_day'] || $next['half_day_type'] === 'morning');
            }

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
