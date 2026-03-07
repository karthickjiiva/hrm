<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\MonthlyLeaveSummary;
use App\Models\LeaveSalaryStatement;
use App\Models\ReportHistory;
use App\Models\PayrollNew;
use App\Models\EmployeeInsurance;
use App\Models\User;
use Illuminate\Http\Request;
use Examyou\RestAPI\ApiResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;
use App\Exports\LeaveListExport;
use App\Exports\EmployeeInsuranceExport;

class MasterController extends ApiBaseController
{
    
    public function getEmployeesList()
    {
        try {
            $users = \App\Models\User::select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();
    
            return response()->json([
                'success' => true,
                'data' => $users
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'Failed to fetch employees: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function master_employee_export()
    {
        try { 
            $users = \App\Models\User::with(['employeeType', 'location', 'designation'])
                ->orderBy('name', 'asc')
                ->get();
    
            if ($users->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No employee records found.'], 404);
            }
    
            $filename = "Employee_Master_" . date('d_M_Y') . ".xlsx";
            $filePath = 'exports/' . $filename;
    
            \Maatwebsite\Excel\Facades\Excel::store(new \App\Exports\EmployeeMasterExport($users), $filePath, 'public');
    
            return response()->json([
                'success' => true,
                'download_url' => asset('storage/' . $filePath),
                'filename' => $filename
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()], 500);
        }
    }


public function master_payroll_export(Request $request)
{
    
    $year = $request->get('year', date('Y'));

    try {
        
        $payrolls = \App\Models\PayrollNew::with(['employee.designation', 'employee.employeeType'])
            ->where('year', $year)
            ->whereHas('employee', function ($query) {
                // Filter out Admin and ensure it only pulls Staff
                $query->where('name', '!=', 'Admin');
            })
            ->select('employee_id') 
            ->selectRaw('SUM(total_earnings) as total_earnings')
            ->selectRaw('SUM(basic) as basic')
            ->selectRaw('SUM(hra) as hra')
            ->selectRaw('SUM(food_allowance) as food_allowance')
            ->selectRaw('SUM(allowance) as allowance')
            ->selectRaw('SUM(pf_employee) as pf_employee')
            ->selectRaw('SUM(esi_employee) as esi_employee')
            ->selectRaw('SUM(total_working_days) as total_working_days')
            ->selectRaw('SUM(loss_of_pay_days) as loss_of_pay_days')
            ->selectRaw('SUM(actual_payable_days) as actual_payable_days')
            ->selectRaw('SUM(tds) as tds')
            ->selectRaw('SUM(professional_tax) as professional_tax')
            ->selectRaw('SUM(net_salary) as net_salary')
            ->groupBy('employee_id') 
            ->get();

        if ($payrolls->isEmpty()) {
            return response()->json([
                'success' => false, 
                'message' => "No payroll records found for the year $year."
            ], 404);
        }

        $filename = "Annual_Payroll_Master_{$year}_" . time() . ".xlsx";
        $filePath = 'exports/' . $filename;

        \Maatwebsite\Excel\Facades\Excel::store(
            new \App\Exports\AnnualPayrollMasterExport($payrolls, $year), 
            $filePath, 
            'public'
        );

        return response()->json([
            'success' => true,
            'download_url' => asset('storage/' . $filePath),
            'filename' => $filename
        ]);

    } catch (\Exception $e) {
        \Log::error("Payroll Export Error: " . $e->getMessage());

        return response()->json([
            'success' => false, 
            'message' => 'Export failed: ' . $e->getMessage()
        ], 500);
    }
}

    public function generateSettlementReport(Request $request)
    {
        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            
            $filename = "Settlement_Report_" . $user->name . "_" . now()->format('d_M_Y') . ".xlsx";
            $filePath = 'exports/' . $filename;
    
            \Maatwebsite\Excel\Facades\Excel::store(
            new \App\Exports\SettlementReportExport(
    $user,
    $request->from_date,
    $request->to_date,
    [
        'siteAdvance' => $request->site_advance,
        'others' => $request->others,
    ]
),
                $filePath, 
                'public'
            );
    
            return response()->json([
                'success' => true,
                'download_url' => asset('storage/' . $filePath),
                'filename' => $filename,
                'message' => 'Report generated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    public function employeesinfo($id)
{
    $user = User::select(
            'id',
            'spouse_name',
            'address'
        )
        ->where('id', $id)
        ->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Employee not found'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => [
            'spouse_name' => $user->spouse_name,
            'permanent_address' => $user->address
        ]
    ]);
}

public function list_insurance(Request $request)
{
    $query = EmployeeInsurance::with('user:id,name');

    if ($request->search) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('spouse_name', 'like', "%$search%")
              ->orWhere('father_name', 'like', "%$search%")
              ->orWhere('nominee_name', 'like', "%$search%");
        })
        ->orWhereHas('user', function ($q) use ($search) {
            $q->where('name', 'like', "%$search%");
        });
    }

    return $query->paginate(10);
}

public function addEmpInsurances(Request $request)
{
    $data = $request->all();

    $record = EmployeeInsurance::updateOrCreate(
        ['user_id' => $request->user_id],
        $data
    );

    return response()->json([
        'success' => true,
        'message' => 'Insurance details saved successfully',
        'data' => $record
    ]);
}

public function show_insurance($id)
{
    $record = EmployeeInsurance::with('user:id,name')
        ->findOrFail($id);

    return response()->json([
        'success' => true,
        'data' => $record
    ]);
}

public function delete_insurance($id)
{
    EmployeeInsurance::findOrFail($id)->delete();

    return response()->json([
        'success' => true,
        'message' => 'Deleted successfully'
    ]);
}

public function generateInsuranceReport()
{
    $filename = 'employee_insurance_report_' . time() . '.xlsx';

    Excel::store(
        new EmployeeInsuranceExport(),
        'reports/' . $filename,
        'public'
    );

    return response()->json([
        'success' => true,
        'message' => 'Report generated successfully',
        'download_url' => asset('storage/reports/' . $filename),
        'filename' => $filename
    ]);
}
 
}