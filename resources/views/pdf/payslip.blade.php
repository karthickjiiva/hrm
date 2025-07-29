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
use Carbon\CarbonTimeZone;
use App\Models\Holiday;
use App\Models\Leave;


class PayrollNewController extends ApiBaseController
{
    protected $model = PayrollNew::class;

public function index()
{
    $request = request();
    $query = PayrollNew::with('employee')
        ->select('payroll_new.*');
 
    // Apply month filter if provided
    if ($request->has('month')) {
        $query->where('month', $request->month);
    }
    
    // Apply year filter if provided
    if ($request->has('year')) {
        $query->where('year', $request->year);
    }
    
 
    
    // Get paginated results
    $payrolls = $query->paginate($request->get('limit', 10));
    
    // \Log::info($query->toSql());
    // \Log::info($query->getBindings());

    return ApiResponse::make(null, $payrolls->items(), [
        'pagination' => [
            'total' => $payrolls->total(),
            'count' => $payrolls->count(),
            'per_page' => $payrolls->perPage(),
            'current_page' => $payrolls->currentPage(),
            'total_pages' => $payrolls->lastPage()
        ]
    ]);
}


    public function downloadPayslip($xid)
    {
          $decoded = Hashids::decode($xid);

        if (empty($decoded)) {
            abort(404, 'Invalid XID');
        }

        $id = $decoded[0]; 
        if (!$id) {
            abort(404, 'Payroll record not found');
        }
 
        $payroll = PayrollNew::with([
            'employee.designation',
            'employee.department',
        ])->findOrFail($id);       

        $pdf = Pdf::loadView('pdf.payslip', compact('payroll'));
        return $pdf->download("payslip-{$payroll->employee->name}.pdf");
    }

    public function generatePayroll(Request $request){

        $month = $request->month;
        $year = $request->year;
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

    protected function revertSingleEmployee($employeeId, $month, $year)
    {
        $payroll = PayrollNew::where('month', $month)
            ->where('year', $year)
            ->where('employee_id', $employeeId)
            ->firstOrFail();

        $this->revertPayrollRecord($payroll);

        return $this->makeSuccessResponse('Payroll reverted successfully for employee');
    }
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

protected function calculateWorkingDays($month, $year)
{
    $date = Carbon::create($year, $month, 1);
    $daysInMonth = $date->daysInMonth;
    // $workingDays = 0;
    
    // // Get all holidays for this month
    // $holidays = Holiday::where('month', $month)
    //     ->where('year', $year)
    //     ->pluck('date')
    //     ->map(function ($date) {
    //         return Carbon::parse($date)->format('Y-m-d');
    //     })
    //     ->toArray();

    // for ($day = 1; $day <= $daysInMonth; $day++) {
    //     $currentDate = $date->copy()->day($day);
        
    //     // Check if it's a working day (not weekend and not holiday)
    //     if (!$currentDate->isWeekend() && !in_array($currentDate->format('Y-m-d'), $holidays)) {
    //         $workingDays++;
    //     }
    // }

    return $daysInMonth;
}
    protected function generateEmployeePayroll($employee, $month, $year)
{
    // Calculate total working days in month
    $workingDays = $this->calculateWorkingDays($month, $year);
    
    // Check leave status and calculate payable days
    $leaveStatus = $this->checkEmployeeLeaveStatus($employee->id, $month, $year);
    
    if ($leaveStatus['has_leave']) {
       
        // Employee has taken leave - use detailed calculation
        $leaveDayss = $this->calculateLossOfPay(
            $employee->id, 
            $month, 
            $year
        );
        // print_r($leaveDayss['loss_of_pay_days']);exit;
        $actual_payable_days = $workingDays;
        $getLeavededuct_lossPay = $this->decreaseLeaveCredits($employee->id , $leaveDayss['loss_of_pay_days']);
        $payableDays = $workingDays - $getLeavededuct_lossPay['remainder'];
        $lossOfPayDays = $getLeavededuct_lossPay['remainder'];

        // Update employee leave balances
    } else {
        // No leaves taken 
        $actual_payable_days = $workingDays;
        $payableDays = $workingDays;
        $lossOfPayDays = 0;
    }
    
        $days_payable = $workingDays;
        $totalEarnings = (float)$employee->basic_salary + (float)$employee->monthly_hra_percent_monthly + (float)$employee->monthly_allowance_percent + (float)$employee->monthly_food_allowance_percent;
        $per_day_salary = $this->calculatePerDaySalary($employee, $employee->monthly_allowance_percent , $employee->monthly_food_allowance_percent, $workingDays);
        if($lossOfPayDays > 0){
            $lossofpayAmount = $per_day_salary * $lossOfPayDays;
            $totalEarnings =  $totalEarnings - (float)$lossofpayAmount;
        }
    $payrollData = [
        'employee_id' => $employee->id,
        'month' => $month,
        'year' => $year,
        'total_working_days' => $workingDays,
        'loss_of_pay_days' => $lossOfPayDays,
        'actual_payable_days' => $payableDays,
        'days_payable' => $days_payable,
        'basic' =>  $employee->basic_salary,
        'hra' =>  $employee->monthly_hra_percent_monthly,  
        'allowance' =>  $employee->monthly_allowance_percent,   
        'food_allowance' =>  $employee->monthly_food_allowance_percent,    // Calculate totals
        'totalEarnings' => $totalEarnings,
        'per_day_salary'=> $per_day_salary,
        'pf_employee'=>0.00,
        'esi_employee'=>0.00,
        'professional_tax'=>0.00,
        'tds'=>0.00,
    ];
    $payrollData['pf_employee'] = $employee->pf_enabled ? $employee->monthly_pf : 0;
    $payrollData['esi_employee'] = $employee->esi_enabled ? $employee->monthly_esi : 0;
    $payrollData['professional_tax'] = $employee->prof_tax_enabled ? $employee->monthly_prof_tax : 0;
    $payrollData['tds'] = $employee->tds_enabled ? $employee->monthly_tds : 0;
    $payrollData['total_earnings'] = $totalEarnings;
    

    $payrollData['total_contributions'] = $payrollData['pf_employee'] + $payrollData['esi_employee'];
    $payrollData['total_taxes_deductions'] = $payrollData['tds'] +  $payrollData['professional_tax'];
    $payrollData['net_salary'] = round($payrollData['total_earnings'] 
                               - $payrollData['total_contributions'] 
                               - $payrollData['total_taxes_deductions'],2);
    
   
    // Create payroll record
    $payroll = PayrollNew::create($payrollData);
    //  print_r($employee);exit;

    $this->updateLeaveCredits($employee->id, $payableDays);

    return $payroll;
}

function calculatePerDaySalary($employee, $allowance = null, $foodAllowance = null, $workingDays) {
    // Initialize total earnings with basic salary (mandatory)
    $totalEarnings = (float)$employee->basic_salary;
    
    if (!empty($employee->monthly_hra_percent_monthly)) {
        $totalEarnings += (float)$employee->monthly_hra_percent_monthly;
    }
    
    if (!empty($allowance)) {
        $totalEarnings += (float)$allowance;
    }
    
    if (!empty($foodAllowance)) {
        $totalEarnings += (float)$foodAllowance;
    }
    
    // Determine the divisor based on how many additional components are present
    $divisor = $workingDays; // Basic (1) + additional components
    
    // Calculate per day salary
    $perDay = $totalEarnings / $divisor;
    
    return round($perDay, 2);
}

public function updateLeaveCredits($employeeId, $workingDays, $createdBy = null)
{
    $leaveRecord = \App\Models\EmployeeLeaveMaster::firstOrNew([
        'employee_id' => $employeeId
    ]);
    $workingDays = 25;
    if (!$leaveRecord->exists) {
        $leaveRecord->cl = 0;
        $leaveRecord->sl = 0;
        $leaveRecord->el = 0;
        
        if ($createdBy) {
            $leaveRecord->created_by = $createdBy;
        }
    }
    $clIncrement = 0;
    $slIncrement = 0;
    $elIncrement = 0;
    
    if ($workingDays > 25) {
        $clIncrement = 1;
        $slIncrement = 1;
        $elIncrement = 1;
    } elseif ($workingDays > 17) {
        $clIncrement = 1;
        $slIncrement = 1;
    } elseif ($workingDays > 10) {
        $clIncrement = 1;
    }
    
    $leaveRecord->cl += $clIncrement;
    $leaveRecord->sl += $slIncrement;
    $leaveRecord->el += $elIncrement;
    
    // Set updated_by if provided
    if ($createdBy) {
        $leaveRecord->updated_by = $createdBy;
    }
    
    // Save the record (will create if new)
    $leaveRecord->save();
    return $leaveRecord;
}
public function decreaseLeaveCredits($employeeId, $lossPayDays, $createdBy = null)
{
    $leaveRecord = \App\Models\EmployeeLeaveMaster::where('employee_id', $employeeId)->first();

    if (!$leaveRecord) {
        return [
            'remainder' => $lossPayDays,
            'message' => 'No leave record found for this employee'
        ];
    }

    $originalLossPayDays = $lossPayDays;
    $remainingDays = $lossPayDays;
    
    // Define leave caps (minimum balance to maintain for each type)
    $caps = [
        'cl' => 0,  // Maintain at least 3 CL
        'sl' => 0,  // Maintain at least 2 SL
        'el' => 0   // No cap for EL
    ];
    
    // Track deductions
    $deductions = [
        'cl' => 0,
        'sl' => 0,
        'el' => 0
    ];

    // Deduction logic for each leave type in order
    $leaveTypes = ['cl', 'sl', 'el'];
    
    foreach ($leaveTypes as $type) {
        if ($remainingDays <= 0) break;
        
        $available = $leaveRecord->$type - $caps[$type];
        
        if ($available > 0) {
            // Can deduct up to the available amount (including fractions)
            $deducted = min($available, $remainingDays);
            
            $leaveRecord->$type -= $deducted;
            $deductions[$type] += $deducted;
            $remainingDays -= $deducted;
        }
    }

    // Set updated_by if provided
    if ($createdBy) {
        $leaveRecord->updated_by = $createdBy;
    }
    
    // Save the record
    $leaveRecord->save();

    return [
        'original_loss_pay_days' => $originalLossPayDays,
        'cl_deducted' => $deductions['cl'],
        'sl_deducted' => $deductions['sl'],
        'el_deducted' => $deductions['el'],
        'remainder' => $remainingDays,
        'new_balances' => [
            'cl' => $leaveRecord->cl,
            'sl' => $leaveRecord->sl,
            'el' => $leaveRecord->el
        ],
        'message' => $remainingDays > 0 
            ? 'Partially deducted (insufficient leave credits)' 
            : 'Leave credits fully deducted'
    ];
}


public function calculateLossOfPay($employeeId, $month, $year)
{
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = Carbon::create($year, $month, 1)->endOfMonth();

    // Get all leaves for the month
    $leaves = Leave::where('user_id', $employeeId)
        ->whereBetween('leave_date', [$startDate, $endDate])
        ->orderBy('leave_date')
        ->get();

    $totalLeaveDays = $this->countValidLeaveDays($leaves, $startDate, $endDate);
    // $leaves = Leave::where('user_id', $employeeId)
    // ->whereBetween('leave_date', [$startDate, $endDate])
    // ->orderBy('leave_date')
    // ->get()
    // ->toArray();
    $lossOfPayDays = $this->calculateExtendedLossOfPay($leaves, $startDate, $endDate);
    return [
        'loss_of_pay_days' => $lossOfPayDays,
        'total_leave_days' => $totalLeaveDays
    ];
}
// use Carbon\Carbon;

/**
 * Calculate Loss of Pay (LOP) days.
 * - Morning half-day is always 0.5, does NOT trigger block weekend/holiday bridging.
 * - Weekends/holidays are only absorbed if part of a consecutive leave block (no working day break).
 */
protected function calculateExtendedLossOfPay($leaves, $startDate, $endDate)
{
    $processedDates = [];
    $lossOfPayDays = 0;
    $leaveMap = [];

    // Create a map of all leave days
    foreach ($leaves as $leave) {
        $leaveDate = Carbon::parse($leave->leave_date);
        $dateString = $leaveDate->toDateString();
        
        if (!$leaveDate->isWeekend() && !$this->isHoliday($leaveDate)) {
            $leaveMap[$dateString] = [
                'is_half_day' => $leave->is_half_day,
                'half_day_type' => $leave->half_day_type ?? null,
                'date' => $leaveDate
            ];
        }
    }

    // Sort leaves by date
    ksort($leaveMap);

    foreach ($leaveMap as $dateString => $leave) {
        if (isset($processedDates[$dateString])) continue;

        $currentDate = $leave['date'];
        $isHalfDay = $leave['is_half_day'];
        $isEvening = $isHalfDay && $leave['half_day_type'] === 'evening';
        $isMorning = $isHalfDay && $leave['half_day_type'] === 'morning';

        if ($isMorning) {
            // Case 2 & 3: Morning half-day - count only 0.5
            $lossOfPayDays += 0.5;
            $processedDates[$dateString] = true;
            continue;
        }

        // Initialize range
        $rangeStart = $currentDate;
        $rangeEnd = $currentDate;
        $hasEvening = $isEvening;
        $currentCount = $isHalfDay ? 0.5 : 1;
        $processedDates[$dateString] = true;

        // Process forward from current leave
        $nextDate = $currentDate->copy()->addDay();
        $tempHolidaysWeekends = 0;

        while ($nextDate->lte($endDate)) {
            $nextString = $nextDate->toDateString();
            
            if (isset($processedDates[$nextString])) {
                $nextDate->addDay();
                continue;
            }

            // Check if non-working day
            if ($nextDate->isWeekend() || $this->isHoliday($nextDate)) {
                $tempHolidaysWeekends++;
                $processedDates[$nextString] = true;
                $nextDate->addDay();
                continue;
            }

            // Check if next working day has leave
            if (!isset($leaveMap[$nextString])) break;
            
            $nextLeave = $leaveMap[$nextString];
            $nextIsHalfDay = $nextLeave['is_half_day'];
            $nextIsEvening = $nextIsHalfDay && $nextLeave['half_day_type'] === 'evening';
            $nextIsMorning = $nextIsHalfDay && $nextLeave['half_day_type'] === 'morning';

            // Case 1 & 4: Evening half-day followed by full day
            if ($hasEvening && $nextIsMorning) {
                $currentCount += $tempHolidaysWeekends + 0.5;
                $tempHolidaysWeekends = 0;
                $processedDates[$nextString] = true;
                $rangeEnd = $nextDate;
                break;
            }
            // Case 1: Full day in sequence
            elseif (!$nextIsHalfDay) {
                $currentCount += $tempHolidaysWeekends + 1;
                $tempHolidaysWeekends = 0;
                $processedDates[$nextString] = true;
                $rangeEnd = $nextDate;
                $hasEvening = $nextIsEvening;
                $nextDate->addDay();
            }
            // Case 4: Evening half-day followed by another evening
            elseif ($nextIsEvening) {
                $currentCount += $tempHolidaysWeekends + 0.5;
                $tempHolidaysWeekends = 0;
                $processedDates[$nextString] = true;
                $rangeEnd = $nextDate;
                $hasEvening = true;
                $nextDate->addDay();
            }
            else {
                break;
            }
        }

        // Add remaining buffer if we have evening half-day
        if ($hasEvening) {
            $currentCount += $tempHolidaysWeekends;
        }

        $lossOfPayDays += $currentCount;
    }

    return $lossOfPayDays;
}

    /**
     * Helper to check holidays.
     * Replace or extend this method according to your actual holidays.
     * 
     * @param Carbon $date
     * @return bool
     */

protected function calculateRangeLoss($startDate, $endDate, $hasEvening)
{
    $count = 0;
    
    // Count all working days in range
    $current = $startDate->copy();
    while ($current->lte($endDate)) {
        if (!$current->isWeekend() && !$this->isHoliday($current)) {
            $count += 1;
        }
        $current->addDay();
    }
    
    // For ranges starting with evening half-day, count next holidays/weekends
    if ($hasEvening) {
        $current = $endDate->copy()->addDay();
        while ($current->isWeekend() || $this->isHoliday($current)) {
            $count += 1;
            $current->addDay();
        }
    }
    
    // For full day ranges, count adjacent holidays/weekends
    if (!$hasEvening) {
        // Before range
        $current = $startDate->copy()->subDay();
        while ($current->isWeekend() || $this->isHoliday($current)) {
            $count += 1;
            $current->subDay();
        }
        
        // After range
        $current = $endDate->copy()->addDay();
        while ($current->isWeekend() || $this->isHoliday($current)) {
            $count += 1;
            $current->addDay();
        }
    }
    
    return $count;
}

protected function checkNextExtendedDays(Carbon $date, $userId)
{
    $current = $date->copy()->addDay();
    $count = 0;
    $processedDates = [];
    $lastWorkingDayWithLeave = null;

    while (true) {
        $dateString = $current->toDateString();

        // Skip if already processed
        if (isset($processedDates[$dateString])) {
            $current->addDay();
            continue;
        }

        // If working day
        if (!$current->isWeekend() && !$this->isHoliday($current)) {
            if ($this->hasLeave($userId, $current)) {
                $count += 1;
                $lastWorkingDayWithLeave = $current;
                $processedDates[$dateString] = true;
                $current->addDay();
            } else {
                break;
            }
        }
        // If holiday/weekend
        else {
            // Only count if it's between two leave days
            if ($lastWorkingDayWithLeave || $this->hasAdjacentLeave($current, $userId, 'next')) {
                $count += 1;
            }
            $processedDates[$dateString] = true;
            $current->addDay();
        }
    }

    return [
        'count' => $count,
        'processed_dates' => $processedDates
    ];
}

protected function checkPreviousExtendedDays(Carbon $date, $userId)
{
    $current = $date->copy()->subDay();
    $count = 0;
    $processedDates = [];
    $lastWorkingDayWithLeave = null;

    while (true) {
        $dateString = $current->toDateString();

        // Skip if already processed
        if (isset($processedDates[$dateString])) {
            $current->subDay();
            continue;
        }

        // If working day
        if (!$current->isWeekend() && !$this->isHoliday($current)) {
            if ($this->hasLeave($userId, $current)) {
                $count += 1;
                $lastWorkingDayWithLeave = $current;
                $processedDates[$dateString] = true;
                $current->subDay();
            } else {
                break;
            }
        }
        // If holiday/weekend
        else {
            // Only count if it's between two leave days
            if ($lastWorkingDayWithLeave || $this->hasAdjacentLeave($current, $userId, 'previous')) {
                $count += 1;
            }
            $processedDates[$dateString] = true;
            $current->subDay();
        }
    }

    return [
        'count' => $count,
        'processed_dates' => $processedDates
    ];
}
protected function hasAdjacentLeave(Carbon $date, $userId, $direction)
{
    if ($direction === 'next') {
        $checkDate = $date->copy()->addDay();
        while ($checkDate->isWeekend() || $this->isHoliday($checkDate)) {
            $checkDate->addDay();
        }
        return $this->hasLeave($userId, $checkDate);
    } else {
        $checkDate = $date->copy()->subDay();
        while ($checkDate->isWeekend() || $this->isHoliday($checkDate)) {
            $checkDate->subDay();
        }
        return $this->hasLeave($userId, $checkDate);
    }
}
protected function getLeaveType($userId, Carbon $date)
{
    // Implement your leave type checking
    $leave = Leave::where('user_id', $userId)
        ->whereDate('leave_date', $date)
        ->first();

    return $leave ? ($leave->is_half_day ? $leave->half_day_type : 'full') : null;
}

protected function isHoliday(Carbon $date)
{
    // Implement your holiday checking
    return Holiday::whereDate('date', $date)->exists();
}

protected function checkPreviousDaysWithAttendance(Carbon $date, array &$processedDates, $userId)
{
    $current = $date->copy()->subDay();
    $count = 0;
    
    while (true) {
        $dateString = $current->toDateString();
        
        // Stop if we hit a processed date or non-leave working day
        if(isset($processedDates[$dateString])) {
            break;
        }
        
        // For working days only
        if (!$current->isWeekend() && !$this->isHoliday($current)) {
            if ($this->hasLeave($userId, $current)) {
                // Mark this date as processed
                $processedDates[$dateString] = true;
                $count++;
            } else {
                // Found working day without leave - stop counting
                break;
            }
        }
        
        $current->subDay();
    }
    
    return $count;
}

protected function checkNextDaysWithAttendance(Carbon $date, array &$processedDates, $userId,$typrrr)
{
    $current = $date->copy()->addDay();
    $count = 0;
    $lastLeaveDate = null;
    
    while (true) {
        $dateString = $current->toDateString();
        
        // Stop if we hit a non-leave working day
        if (!$current->isWeekend() && !$this->isHoliday($current)) {
            if (!$this->hasLeave($userId, $current)) {
                break;
            }
            $lastLeaveDate = $current->copy();
        }
        
        // Skip if already processed (but continue checking beyond)
        if (isset($processedDates[$dateString])) {
            $current->addDay();
            continue;
        }
        
        // For working days only
        if (!$current->isWeekend() && !$this->isHoliday($current)) {
            $processedDates[$dateString] = true;
            $count++;
        }
        
        $current->addDay();
    }
    $totalCount = $count;
    if ($typrrr == 0.5) {
        $totalCount += 0.5;
    } else {
        $totalCount += $typrrr;
    }
    
    return [
        'count' => $totalCount,
        'end_date' => $lastLeaveDate ? $lastLeaveDate->copy() : null
    ];
}

protected function processLeaveRange($userId, Carbon $startDate, Carbon $endDate, array &$processedDates)
{
    $current = $startDate->copy();
    $count = 0;
    
    while ($current->lte($endDate)) {
        $dateString = $current->toDateString();
        
        // Only count working days that aren't already processed
        if (!isset($processedDates[$dateString]) && 
            !$current->isWeekend() && 
            !$this->isHoliday($current)) {
            
            if ($this->hasLeave($userId, $current)) {
                $processedDates[$dateString] = true;
                $count++;
            }
        }
        
        $current->addDay();
    }
    
    return $count;
}


protected function hasLeave($userId, Carbon $date)
{
    return Leave::where('user_id', $userId)
        ->whereDate('leave_date', $date)
        ->exists();
}

protected function findLastWorkingDayBefore(Carbon $date, $userId)
{
    $current = $date->copy()->subDay();
    
    while ($current >= $date->copy()->subMonth()) {
        if (!$current->isWeekend() && !$this->isHoliday($current)) {
            return $current;
        }
        $current->subDay();
    }
    
    return null;
}

protected function findNextWorkingDayAfter(Carbon $date, $userId)
{
    $current = $date->copy()->addDay();
    
    while ($current <= $date->copy()->addMonth()) {
        if (!$current->isWeekend() && !$this->isHoliday($current)) {
            return $current;
        }
        $current->addDay();
    }
    
    return null;
}

protected function countNonWorkingDaysBetween(Carbon $start, Carbon $end)
{
    $count = 0;
    $current = $start->copy();
    
    while ($current <= $end) {
        if ($current->isWeekend() || $this->isHoliday($current)) {
            // Weekend counts as 2 days if it's Saturday
            if ($current->isWeekend() && $current->isSaturday()) {
                $count += 2;
                $current->addDay(); // Skip Sunday
            } else {
                $count += 1;
            }
        }
        $current->addDay();
    }
    
    return $count;
}

protected function hasAttendance($userId, Carbon $date)
{
    // If it's a weekend or holiday, automatically consider no attendance
    if ($date->isWeekend() || $this->isHoliday($date)) {
        return false;
    }

    // Check if there's any leave record for this day (regardless of status)
    $hasLeave = Leave::where('user_id', $userId)
        ->whereDate('leave_date', $date)
        ->exists();

    // If leave record exists, employee was absent
    // If no leave record exists, employee was present
    return !$hasLeave;
}

// protected function calculateExtendedLossOfPay($leaves, $startDate, $endDate)
// {
//     $lossOfPayDays = 0;
//     $processedDates = [];

//     foreach ($leaves as $leave) {
//         $leaveDate = Carbon::parse($leave->leave_date);
        
//         // Skip if already processed or invalid
//         if (isset($processedDates[$leaveDate->toDateString()]) || 
//             $leaveDate->isWeekend() || 
//             $this->isHoliday($leaveDate)) {
//             continue;
//         }

//         $baseValue = $leave->is_half_day ? 0.5 : 1;
//         $extendedDays = 0;

//         // Check adjacent days based on half-day type
//         if ($leave->is_half_day) {
//             if ($leave->half_day_type === 'morning') {
//                 // For morning half-day, only check previous day
//                 $extendedDays += $this->checkPreviousDays($leaveDate, $processedDates);
//             } elseif ($leave->half_day_type === 'evening') {
//                 // For evening half-day, only check next day
//                 $extendedDays += $this->checkNextDays($leaveDate, $processedDates);
//             }
//         } else {
//             // For full day leaves, check both sides
//             $extendedDays += $this->checkPreviousDays($leaveDate, $processedDates);
//             $extendedDays += $this->checkNextDays($leaveDate, $processedDates);
//         }

//         $lossOfPayDays += $baseValue + $extendedDays;
//         $processedDates[$leaveDate->toDateString()] = true;
//     }

//     return $lossOfPayDays;
// }

/**
 * Check previous consecutive days for weekends/holidays
 */
protected function checkPreviousDays(Carbon $date, array &$processedDates)
{
    $current = $date->copy()->subDay();
    $count = 0;
    
    while (true) {
        $dateString = $current->toDateString();
        
        // Stop if we hit a working day or processed date
        if (!$current->isWeekend() && !$this->isHoliday($current) || 
            isset($processedDates[$dateString])) {
            break;
        }
        
        // Weekend counts as 2 days (Saturday and Sunday)
        if ($current->isWeekend() && $current->isSunday()) {
            $count += 2;
            $processedDates[$dateString] = true;
            $processedDates[$current->copy()->addDay()->toDateString()] = true;
            $current->subDay(); // Skip Sunday since we counted both
        } 
        // Single holiday or Sunday
        else {
            $count += 1;
            $processedDates[$dateString] = true;
        }
        
        $current->subDay();
    }
    
    return $count;
}

/**
 * Check next consecutive days for weekends/holidays
 */
protected function checkNextDays(Carbon $date, array &$processedDates)
{
    $current = $date->copy()->addDay();
    $count = 0;
    
    while (true) {
        $dateString = $current->toDateString();
        
        // Stop if we hit a working day or processed date
        if (!$current->isWeekend() && !$this->isHoliday($current) || 
            isset($processedDates[$dateString])) {
            break;
        }
        
        // Weekend counts as 2 days (Saturday and Sunday)
        if ($current->isWeekend() && $current->isSaturday()) {
            $count += 2;
            $processedDates[$dateString] = true;
            $processedDates[$current->copy()->addDay()->toDateString()] = true;
            $current->addDay(); // Skip Sunday since we counted both
        } 
        // Single holiday or Sunday
        else {
            $count += 1;
            $processedDates[$dateString] = true;
        }
        
        $current->addDay();
    }
    
    return $count;
}

/**
 * Check if a date is a holiday (you need to implement your holiday check logic)
 */
// protected function isHoliday(Carbon $date)
// {
//     // Implement your holiday checking logic here
//     // Example: return Holiday::where('date', $date->toDateString())->exists();
//     return false;
// }
protected function calculateDaysWithLeave($employeeId, $month, $year, $totalWorkingDays, $leaveType)
{
    $startDate = Carbon::create($year, $month, 1)->startOfMonth();
    $endDate = Carbon::create($year, $month, 1)->endOfMonth();

    // Get all leaves for the month
    $leaves = Leave::where('user_id', $employeeId)
        ->whereBetween('leave_date', [$startDate, $endDate])
        ->orderBy('leave_date')
        ->get();
    
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