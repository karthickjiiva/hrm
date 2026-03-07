<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\MonthlyLeaveSummary;
use App\Models\LeaveSalaryStatement;
use App\Models\ReportHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Examyou\RestAPI\ApiResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalaryLeavesExport;
use App\Exports\LeaveSalaryStatementExport;

class SalaryLeaveController extends ApiBaseController
{
    protected $model = MonthlyLeaveSummary::class;

public function index()
{
    $request = request();

    $query = MonthlyLeaveSummary::query()
        ->selectRaw('month, year, COUNT(*) as total_summaries');

    if ($request->filled('year')) {
        $query->where('year', $request->year);
    }

    if ($request->filled('month')) {
        $query->where('month', $request->month);
    }

    $query->groupBy('year', 'month')
          ->orderBy('year', 'desc')
          ->orderBy('month', 'desc');

    $summaries = $query->paginate($request->get('limit', 10));

    $data = $summaries->getCollection()->map(function ($row) {
        return [
            'month' => (int) $row->month,
            'year'  => (int) $row->year,
            'total_summaries' => (int) $row->total_summaries,
            'month_name' => date('F', mktime(0, 0, 0, $row->month, 1)),
        ];
    })->values();

    return response()->json([
        'data' => $data,
        'meta' => [
            'paging' => [
                'total'        => $summaries->total(),
                'count'        => $summaries->count(),
                'per_page'     => $summaries->perPage(),
                'current_page' => $summaries->currentPage(),
                'total_pages'  => $summaries->lastPage(),
            ]
        ]
    ]);
}

public function export(Request $request)
{
    $month = $request->get('month');
    $year = $request->get('year');

    if (!$month || !$year) {
        return response()->json(['error' => 'Month and Year are required'], 422);
    }

    $fileName = "salaryleaves_statements_{$month}_{$year}.xlsx";
    $filePath = "exports/{$fileName}";

    // Store the file in storage/app/public/exports
    Excel::store(new SalaryLeavesExport($month, $year), $filePath, 'public');

    return response()->json([
        'download_url' => asset("storage/{$filePath}"),
        'filename' => $fileName,
    ]);
}


 public function statementGenerate(Request $request)
{
    $year = $request->get('year');
    if (!$year) return response()->json(['error' => 'Year is required'], 422);

    $exists = ReportHistory::where('report_name', 'Leave Salary Statement')
                ->where('year', $year)
                ->exists();
    
    if ($exists) {
        return response()->json([
            'message' => "The Leave Salary Statement for {$year} has already been generated."
        ], 422);
    }
    
    $employees = User::where('name', '!=', 'Admin')
        ->whereHas('employeeType', function ($q) {
            $q->where('type', '!=', 'Consultant Emp');
        })
        ->with(['leaveMaster'])  
        ->get();

    \DB::transaction(function () use ($year, $employees) {
        foreach ($employees as $employee) {
            $master = $employee->leaveMaster;
            if (!$master) continue;

            $elBalance = $master->el ?? 0;
            $gross = $employee->monthly_amount ?? 0;
            $leaveSalary = ($gross > 0) ? ($gross / 31) * $elBalance : 0;

            LeaveSalaryStatement::create([
                'employee_id'  => $employee->id, 
                'year'         => $year,
                'new_gross'    => round($gross, 2),        
                'earned_leave' => $elBalance,
                'leave_salary' => round($leaveSalary, 0) 
            ]);
            
            $newSl = $master->sl > 12 ? 12 : $master->sl;
            $master->update([
                'el' => 0,
                'cl' => 0,
                'sl' => $newSl
            ]);
        }
    });

    $fileName = "leave_salary_statement_{$year}_" . time() . ".xlsx";
    $filePath = "exports/statements/{$fileName}";

    $bankFile = "leave_salary_bank_statement_{$year}_" . time() . ".xlsx";
    $bankPath = "exports/statements/{$bankFile}";

    Excel::store(new LeaveSalaryStatementExport($year), $filePath, 'public');
    Excel::store(new LeaveSalaryBankExport($year), $bankPath, 'public');

    ReportHistory::create([
        'year' => $year, 
        'report_name' => 'Leave Salary Statement',
        'filename' => $fileName, 
        'path' => $filePath,
        'bank_filename' => $bankFile, 
        'bank_path' => $bankPath
    ]);

    return response()->json(['message' => "Report for {$year} generated and leaves reset successfully."]);
}

public function statementHistory()
{
    $limit = request('limit', 10);
    $history = ReportHistory::where('report_name', 'Leave Salary Statement')
        ->orderBy('created_at', 'desc')
        ->paginate($limit);

    $data = $history->getCollection()->map(function ($row) {
        return [
            'id' => $row->id,
            'year' => $row->year,
            'period_label' => "Jan {$row->year} - Dec {$row->year}",
            'filename' => $row->filename,
            'created_at_formatted' => $row->created_at->format('d-M-Y H:i'),
            'download_url' => asset("storage/{$row->path}"), 
        ];
    });

    return response()->json([
        'data' => $data,
        'meta' => [
            'paging' => [
                'total' => $history->total(),
            ]
        ]
    ]);
}

}