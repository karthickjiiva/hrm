<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\PayrollNew;
use App\Models\LeaveAdjustment;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use App\Models\Holiday;
use App\Models\Leave;

class PayrollNewController extends ApiBaseController
{
    protected $model = PayrollNew::class;

    /**
     * Generate payroll - handles both single employee and all employees
     */
    public function generatePayroll(Request $request){

        $month = $request->month;
        $year = $request->year;
        // $employeeId = $request->employee_id;

        // if ($employeeId) {
        //     // Single employee processing
        //     return $this->processSingleEmployee($employeeId, $month, $year);
        // }

        // Process all active employees
        return $this->processAllEmployees($month, $year);
    }

    /**
     * Revert payroll - handles both single employee and all employees
     */
    public function revertPayroll(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020',
            'employee_id' => 'nullable|exists:staff_members,id'
        ]);

        $month = $request->month;
        $year = $request->year;
        $employeeId = $request->employee_id;

        if ($employeeId) {
            // Revert single employee payroll
            return $this->revertSingleEmployee($employeeId, $month, $year);
        }

        // Revert all payrolls for the month/year
        return $this->revertAllEmployees($month, $year);
    }

    /**
     * Process payroll for single employee
     */
    protected function processSingleEmployee($employeeId, $month, $year)
    {
        // Check if payroll already exists
        if (PayrollNew::where('month', $month)
            ->where('year', $year)
            ->where('employee_id', $employeeId)
            ->exists()) {
            return $this->makeErrorResponse('Payroll already generated for this employee and period', 409);
        }

        $employee = StaffMember::findOrFail($employeeId);
        $payroll = $this->generateEmployeePayroll($employee, $month, $year);

        return $this->makeSuccessResponse('Payroll generated successfully', $payroll);
    }

    /**
 * Standard success response format
 */
protected function makeSuccessResponse($message, $data = [])
{
    return [
        'success' => true,
        'message' => $message,
        'data' => $data
    ];
}

/**
 * Standard error response format
 */
protected function makeErrorResponse($message, $code = 400, $errors = [])
{
    return response()->json([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $code);
}

    /**
     * Process payroll for all active employees
     */
    protected function processAllEmployees($month, $year) 
    {
        // Check if any payroll exists for this period

        $employees = StaffMember::where('status', 'active')
                ->where('id', 7)
                ->get();
        $generated = [];
        $errors = [];

        foreach ($employees as $employee) {
            try {
                $payroll = $this->generateEmployeePayroll($employee, $month, $year);
                $generated[] = $payroll->id;
            } catch (\Exception $e) {
                $errors[] = [
                    'employee_id' => $employee->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $this->makeSuccessResponse('Payroll generated for all employees', [
            'generated_count' => count($generated),
            'error_count' => count($errors),
            'errors' => $errors
        ]);
    }

    /**
     * Revert payroll for single employee
     */
    protected function revertSingleEmployee($employeeId, $month, $year)
    {
        $payroll = PayrollNew::where('month', $month)
            ->where('year', $year)
            ->where('employee_id', $employeeId)
            ->firstOrFail();

        $this->revertPayrollRecord($payroll);

        return $this->makeSuccessResponse('Payroll reverted successfully for employee');
    }

    /**
     * Revert payroll for all employees in period
     */
    protected function revertAllEmployees($month, $year)
    {
        $payrolls = PayrollNew::where('month', $month)
            ->where('year', $year)
            ->get();

        $reverted = 0;
        $errors = [];

        foreach ($payrolls as $payroll) {
            try {
                $this->revertPayrollRecord($payroll);
                $reverted++;
            } catch (\Exception $e) {
                $errors[] = [
                    'payroll_id' => $payroll->id,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $this->makeSuccessResponse('Payroll reverted for all employees', [
            'reverted_count' => $reverted,
            'error_count' => count($errors),
            'errors' => $errors
        ]);
    }

    /**
     * Core payroll generation logic for single employee
     */
    protected function generateEmployeePayroll($employee, $month, $year)
{
    // Calculate total working days in month
    $workingDays = $this->calculateWorkingDays($month, $year);
    
    // Check leave status and calculate payable days
    $leaveStatus = $this->checkEmployeeLeaveStatus($employee->id, $month, $year);
    
    if ($leaveStatus['has_leave']) {
        // Employee has taken leave - use detailed calculation
        $payableDays = $this->calculateDaysWithLeave(
            $employee->id, 
            $month, 
            $year, 
            $workingDays,
            $leaveStatus['leave_type']
        );
        $lossOfPayDays = $result['loss_of_pay'];
        $payableDays = $result['payable_days'];
        $leaveDeductions = $result['leave_deductions'];
        
        // Update employee leave balances
        $this->updateLeaveBalances($employee->id, $leaveDeductions);
    } else {
        // No leaves taken - full month calculation
        $payableDays = $workingDays;
        $lossOfPayDays = $workingDays - $payableDays;
    }
    $payrollData = [
        'employee_id' => $employee->id,
        'month' => $month,
        'year' => $year,
        'total_working_days' => $workingDays,
        'loss_of_pay_days' => $lossOfPayDays,
        'days_payable' => $payableDays,
        'basic' => $this->calculateDailyAmountWithDeductions(
        $employee->basic_salary, 
        $workingDays, 
        $payableDays
        ),
    
    'hra' => $this->calculateDailyAmountWithDeductions(
        $employee->monthly_hra_percent_monthly, 
        $workingDays, 
        $payableDays
    ),
    
    'allowance' => $this->calculateDailyAmountWithDeductions(
        $employee->monthly_allowance_percent, 
        $workingDays, 
        $payableDays
    ),
    
    'foodAllowance' => $this->calculateDailyAmountWithDeductions(
        $employee->monthly_food_allowance_percent, 
        $workingDays, 
        $payableDays
    ),

    // Calculate totals
    $totalEarnings = $basic + $hra + $allowance + $foodAllowance
    ];
    $pfAmount = $employee->monthly_pf ?? 0;
        $payrollData['pf_employee'] = $pfAmount;
    $esiAmount = $employee->monthly_esi ?? 0;
        $payrollData['esi_employee'] = $esiAmount;

    // Calculate totals
    $payrollData['total_earnings'] = $totalEarnings;
    $payrollData['total_contributions'] = $this->calculateContributions($payrollData, $employee);
    $payrollData['total_taxes_deductions'] = $this->calculateTaxes($payrollData, $employee);
    $payrollData['net_salary'] = $payrollData['total_earnings'] 
                               - $payrollData['total_contributions'] 
                               - $payrollData['total_taxes_deductions'];
    

    // Create payroll record
    $payroll = PayrollNew::create($payrollData);

    // Process leave adjustments
    $this->processLeaveAdjustments($payroll, $leaveStatus);

    return $payroll;
}
protected function calculateDaysWithLeave($employeeId, $month, $year, $totalWorkingDays, $leaveType)
{
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = Carbon::create($year, $month, 1)->endOfMonth();

    // Get all leaves for the month
    $leaves = Leave::where('user_id', $employeeId)
        ->whereBetween('leave_date', [$startDate, $endDate])
        ->orderBy('leave_date')
        ->get();

    // Count total leave days (excluding weekends/holidays)
    $totalLeaveDays = $this->countValidLeaveDays($leaves, $startDate, $endDate);

    // Calculate deductions using the new rules (max 2 per type)
    $deductionResult = $this->calculateLeaveDeductions($employeeId, $month, $year, $totalLeaveDays);

    // Identify sandwich leaves separately
    $sandwichLeaves = $this->identifySandwichLeaves($leaves, $startDate, $endDate);
    $sandwichDeduction = count($sandwichLeaves);

    // Total loss of pay days
    $totalLossOfPay = $deductionResult['loss_of_pay'] + $sandwichDeduction;

    // Update employee leave balances
    $this->updateEmployeeLeaveBalance(
        $employeeId,
        $deductionResult['deductions'],
        $sandwichDeduction,
        $month,
        $year
    );

    return [
        'payable_days' => max(0, $totalWorkingDays - $totalLossOfPay),
        'leave_deductions' => $deductionResult['deductions'],
        'sandwich_leaves' => $sandwichLeaves,
        'loss_of_pay' => $totalLossOfPay
    ];
}

protected function identifySandwichLeaves($leaves, $startDate, $endDate)
{
    $sandwichLeaves = [];
    $processedDates = [];

    foreach ($leaves as $leave) {
        $leaveDate = Carbon::parse($leave->leave_date);
        $dateString = $leaveDate->format('Y-m-d');

        // Skip if already processed or invalid date
        if (in_array($dateString, $processedDates)) {
            continue;
        }

        // Skip weekends and holidays
        if ($leaveDate->isWeekend() || $this->isHoliday($leaveDate)) {
            continue;
        }

        // Check sandwich pattern
        if ($this->isSandwichPattern($leaveDate, $leaves, $startDate, $endDate)) {
            $sandwichLeaves[] = $dateString;
            $processedDates[] = $dateString;
        }
    }

    return $sandwichLeaves;
}

protected function isSandwichPattern($leaveDate, $leaves, $startDate, $endDate)
{
    $prevDay = $leaveDate->copy()->subDay();
    $nextDay = $leaveDate->copy()->addDay();

    // Check previous day condition
    $prevCondition = $this->isNonWorkingDay($prevDay, $leaves, $startDate, $endDate);

    // Check next day condition
    $nextCondition = $this->isNonWorkingDay($nextDay, $leaves, $startDate, $endDate);

    // Sandwich leave is when leave is between two non-working days
    return ($prevCondition && $this->isWorkingDay($leaveDate)) || 
           ($this->isWorkingDay($leaveDate) && $nextCondition);
}

protected function isNonWorkingDay($date, $leaves, $startDate, $endDate)
{
    // Check if date is within our month range
    if ($date->lt($startDate) || $date->gt($endDate)) {
        return false;
    }

    // Check if it's a weekend or holiday
    if ($date->isWeekend() || $this->isHoliday($date)) {
        return true;
    }

    // Check if there's an approved leave on this date
    foreach ($leaves as $leave) {
        $leaveDate = Carbon::parse($leave->leave_date);
        if ($leaveDate->format('Y-m-d') === $date->format('Y-m-d')) {
            return true;
        }
    }

    return false;
}

protected function isWorkingDay($date)
{
    return !$date->isWeekend() && !$this->isHoliday($date);
}

protected function isHoliday($date)
{
    return Holiday::where('date', $date->format('Y-m-d'))->exists();
}

// New helper function to count valid leave days
protected function countValidLeaveDays($leaves, $startDate, $endDate)
{
    $count = 0;
    
    foreach ($leaves as $leave) {
        $leaveDate = Carbon::parse($leave->leave_date);
        
        // Skip weekends and holidays
        if ($leaveDate->isWeekend() || $this->isHoliday($leaveDate)) {
            continue;
        }
        
        $count += $leave->is_half_day ? 0.5 : 1;
    }
    
    return $count;
}

// Updated leave balance updater
protected function updateEmployeeLeaveBalance($employeeId, $deductions, $sandwichDays, $month, $year)
{
    $employee = StaffMember::find($employeeId);
    
    if ($employee) {
        // Update regular leave balances
        $employee->update([
            'el' => max(0, $employee->el - ($deductions['el'] ?? 0)),
            'sl' => max(0, $employee->sl - ($deductions['sl'] ?? 0)),
            'cl' => max(0, $employee->cl - ($deductions['cl'] ?? 0))
        ]);
        
        // Record loss of pay if any
        if (($deductions['loss_of_pay'] ?? 0) > 0 || $sandwichDays > 0) {
            EmployeeLossOfPay::create([
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year,
                'regular_loss_days' => $deductions['loss_of_pay'] ?? 0,
                'sandwich_loss_days' => $sandwichDays,
                'total_loss_days' => ($deductions['loss_of_pay'] ?? 0) + $sandwichDays
            ]);
        }
    }
}

protected function isSandwichLeave($leaveDate, $allLeaves, $monthStart, $monthEnd)
{
    $prevDay = $leaveDate->copy()->subDay();
    $nextDay = $leaveDate->copy()->addDay();
    
    // Check if adjacent days are working days (not weekend/holiday/leave)
    $isPrevDayWorking = $this->isWorkingDay($prevDay) && 
                       !$allLeaves->contains('leave_date', $prevDay->format('Y-m-d'));
    
    $isNextDayWorking = $this->isWorkingDay($nextDay) && 
                       !$allLeaves->contains('leave_date', $nextDay->format('Y-m-d'));
    
    return $isPrevDayWorking && $isNextDayWorking;
}






protected function isHolidayOrWeekend($date, $employeeId)
{
    return $date->isWeekend() || 
           Holiday::where('date', $date->format('Y-m-d'))->exists();
}

protected function getLeaveTypeCode($leaveType)
{
    // Map your leave types to codes (sl/cl/el)
    $mapping = [
        'sick' => 'sl',
        'casual' => 'cl',
        'earned' => 'el'
    ];
    
    return $mapping[strtolower($leaveType)] ?? 'el';
}

protected function updateLeaveBalances($employeeId, $deductions)
{
    $leaveMaster = EmployeeLeaveMaster::where('employee_id', $employeeId)->first();
    
    if ($leaveMaster) {
        $leaveMaster->update([
            'sl' => max(0, $leaveMaster->sl - $deductions['sl']),
            'cl' => max(0, $leaveMaster->cl - $deductions['cl']),
            'el' => max(0, $leaveMaster->el - $deductions['el'])
        ]);
    }
}

protected function calculateContributions($payrollData, $employee)
{
    $total = 0;
    
    // PF Contribution (fixed amount from employee record)
    if ($employee->pf_enabled) {
        $pfAmount = $employee->monthly_pf ?? 0;
        $payrollData['pf_employee'] = $pfAmount;
        $total += $pfAmount;
    }
    
    // ESI Contribution (fixed amount from employee record)
    if ($employee->esi_enabled) {
        $esiAmount = $employee->monthly_esi ?? 0;
        $payrollData['esi_employee'] = $esiAmount;
        $total += $esiAmount;
    }
    
    return $total;
}
protected function calculateTaxes($payrollData, $employee) 
{
    $total = 0;
    
    // Professional Tax (fixed amount)
    if ($employee->prof_tax_enabled) {
        $profTax = $employee->monthly_prof_tax ?? 0;
        $payrollData['professional_tax'] = $profTax;
        $total += $profTax;
    }
    
    // TDS (fixed amount)
    if ($employee->tds_enabled) {
        $tdsAmount = $employee->monthly_tds ?? 0;
        $payrollData['tds'] = $tdsAmount;
        $total += $tdsAmount;
    }
    
    return $total;
}


protected function updateLeaveMaster($employeeId, $deductions, $month, $year)
{
    $leaveMaster = EmployeeLeaveMaster::where('employee_id', $employeeId)->first();
    
    if ($leaveMaster) {
        // Update only the leaves we actually deducted (not loss of pay days)
        $leaveMaster->update([
            'sl' => max(0, $leaveMaster->sl - $deductions['sl']),
            'cl' => max(0, $leaveMaster->cl - $deductions['cl']),
            'el' => max(0, $leaveMaster->el - $deductions['el']),
            'updated_at' => now()
        ]);
        
        // Record the loss of pay days separately
        if ($lossOfPayDays > 0) {
            EmployeeLossOfPay::create([
                'employee_id' => $employeeId,
                'month' => $month,
                'year' => $year,
                'days' => $lossOfPayDays,
                'reason' => 'Insufficient leave balance'
            ]);
        }
    }
}

/**
 * Check if a date is a holiday
 */


/**
 * Check if employee has leave records for the month
 */
protected function checkEmployeeLeaveStatus($employeeId, $month, $year)
{
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = Carbon::create($year, $month, 1)->endOfMonth();

    $leave = Leave::where('user_id', $employeeId)
        ->where(function($query) use ($startDate, $endDate) {
            $query->whereBetween('leave_date', [$startDate, $endDate])
                  ->orWhere(function($q) use ($startDate, $endDate) {
                      $q->where('start_date', '<=', $endDate)
                        ->where('end_date', '>=', $startDate);
                  });
        })
        ->first();
   return [
        'has_leave' => !is_null($leave),
        'leave_type' => $leave->leave_type ?? null,
        'is_paid' => $leave->is_paid ?? false,
        'is_half_day' => $leave->is_half_day ?? false
   ];
}

/**
 * Calculate payable days when employee has taken leave
 */


/**
 * Process leave adjustments for payroll
 */
protected function processLeaveAdjustments($payroll, $leaveStatus)
{
    if ($leaveStatus['has_leave']) {
        LeaveAdjustment::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $payroll->employee_id,
            'month' => $payroll->month,
            'year' => $payroll->year,
            'type' => 'deduction',
            'days' => $payroll->loss_of_pay_days,
            'status' => 'processed'
        ]);
    }
}

    /**
 * Check if payroll exists for given month/year
 */
public function checkPayrollExists(Request $request)
{
    $request->validate([
        'month' => 'required|integer|between:1,12',
        'year' => 'required|integer|min:2000|max:2100',
        'employee_id' => 'nullable|exists:staff_members,id'
    ]);


    $query = PayrollNew::where('month', $request->month)
        ->where('year', $request->year);

    if ($request->has('employee_id')) {
        $query->where('employee_id', $request->employee_id);
    }

    $exists = $query->exists();

    return response()->json([
        'exists' => $exists,
        'message' => $exists ? 'Payroll exists for this period' : 'No payroll found for this period',
        'count' => $exists ? $query->count() : 0
    ]);
}

    /**
     * Core payroll reverting logic
     */
    protected function revertPayrollRecord($payroll)
    {
        // First handle leave adjustments reversal
        $this->revertLeaveAdjustments($payroll);
        
        // Then delete the payroll record
        $payroll->delete();
    }

    /**
     * Placeholder for leave adjustments processing
     */
    protected function revertLeaveAdjustments($payroll)
    {
        // Will be implemented based on your leave table structure
        // This will reset leave adjustments flags
    }

}