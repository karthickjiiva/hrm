<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use Illuminate\Http\Request;
use App\Models\GeneratedReport;
use App\Exports\BonusReportExport;
use App\Exports\EsiReportExport;
use App\Exports\WageRegisterExport;
use App\Exports\FormXLeaveRegisterExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class ReportController extends ApiBaseController
{
    public function history($type)
    {
        $reports = GeneratedReport::where('report_type', $type)
            ->orderBy('id', 'desc')
            ->paginate(request('limit', 10));

        return response()->json($reports);
    }

public function generateBonus(Request $request)
{
    $year = $request->year;
    $userId = auth()->id();
    $existingReport = GeneratedReport::where('report_type', 'bonus')
                                     ->where('year', $year)
                                     ->first();

    if ($existingReport) {
        $oldPath = str_replace(asset('storage/'), '', $existingReport->file_path);
        if (Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    $filename = "Bonus_Report_{$year}_" . time() . ".xlsx";
    $relativeFolder = "reports/bonus";
    $fullPath = "{$relativeFolder}/{$filename}";
    Excel::store(new BonusReportExport($year), $fullPath, 'public');
    $report = GeneratedReport::updateOrCreate(
        [
            'report_type' => 'bonus',
            'year'        => $year,
        ],
        [
            'filename'   => $filename,
            'file_path'  => $fullPath,
            'created_by' => 1,
        ]
    );

    return response()->json([
        'message' => "Bonus report for $year generated successfully!",
        'data' => $report
    ]);
}

public function generateOfficeEsi(Request $request)
{
    $month = $request->month;
    $year = $request->year;
    $userId = auth()->id();

    $filename = "ESI_Report_{$month}_{$year}_" . time() . ".xlsx";
    $path = "reports/esi/{$filename}";

    Excel::store(new EsiReportExport($month, $year), $path, 'public');

    $report = GeneratedReport::updateOrCreate(
        ['report_type' => 'esi', 'month' => $month, 'year' => $year],
        [
            'filename' => $filename,
            'file_path' => $path,
            'created_by' => 1,
        ]
    );

    return response()->json(['message' => "ESI Report generated!", 'data' => $report]);
}

public function generateWageRegister(Request $request)
{
    $request->validate([
        'month' => 'required|integer|between:1,12',
        'year' => 'required|integer',
    ]);

    $month = $request->month;
    $year = $request->year;
    
    $monthName = date('M', mktime(0, 0, 0, $month, 10));
    $filename = "Wage_Register_{$monthName}_{$year}_" . time() . ".xlsx";
    $path = "reports/wage_register/{$filename}";

    Excel::store(new WageRegisterExport($month, $year), $path, 'public');

    GeneratedReport::updateOrCreate(
        ['report_type' => 'wage_register', 'month' => $month, 'year' => $year],
        [
            'filename' => $filename,
            'file_path' => $path,
            'created_by' => 1,
        ]
    );

    return response()->json([
        'success' => true,
        'message' => "Wage Register for {$monthName} {$year} generated!"
    ]);
}

 public function generateFormX(Request $request)
{
    $request->validate(['month' => 'required', 'year' => 'required']);
    $month = $request->month;
    $year = $request->year;

    // 1. Check if a report already exists for this specific month/year/type
    $existingReport = \App\Models\GeneratedReport::where('report_type', 'form_x')
        ->where('month', $month)
        ->where('year', $year)
        ->first();

    // 2. Setup file naming
    $filename = "Form_X_Leave_Register_" . date('M_Y', mktime(0, 0, 0, $month, 1)) . ".xlsx";
    $path = "reports/form_x/{$filename}";

    // 3. Delete the physical file if it already exists to avoid ghost files
    if ($existingReport && \Storage::disk('public')->exists($existingReport->file_path)) {
        \Storage::disk('public')->delete($existingReport->file_path);
    }

    // 4. Store the new Excel file
    \Maatwebsite\Excel\Facades\Excel::store(new \App\Exports\FormXLeaveRegisterExport($month, $year), $path, 'public');

    // 5. Update the existing record or create a new one (UpdateOrCreate)
    \App\Models\GeneratedReport::updateOrCreate(
        [
            'report_type' => 'form_x',
            'month' => $month,
            'year' => $year,
        ],
        [
            'filename' => $filename,
            'file_path' => $path,
            'created_by' => auth()->id() ?? 1,
            'updated_at' => now(), // Force update the timestamp
        ]
    );

    return response()->json(['success' => true, 'message' => 'Form X Register updated successfully!']);
}

}
?>