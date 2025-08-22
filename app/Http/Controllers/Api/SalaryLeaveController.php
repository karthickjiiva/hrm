<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\MonthlyLeaveSummary;
use Illuminate\Http\Request;
use Examyou\RestAPI\ApiResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalaryLeavesExport;

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

}