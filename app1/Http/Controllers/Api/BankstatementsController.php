<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\BankStatement;
use Illuminate\Http\Request;
use Examyou\RestAPI\ApiResponse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BankStatementsExport;

class BankStatementsController extends ApiBaseController
{
    protected $model = BankStatement::class;

 public function index()
{
    $request = request();

    $query = BankStatement::query()
        ->selectRaw('month, year, COUNT(*) as total_statements');

    // Apply filters
    if ($request->filled('year')) {
        $query->where('year', $request->year);
    }

    if ($request->filled('month')) {
        $query->where('month', $request->month);
    }

    $query->groupBy('year', 'month')
          ->orderBy('year', 'desc')
          ->orderBy('month', 'desc');

    $statements = $query->paginate($request->get('limit', 10));

    // Transform collection into proper array
    $data = $statements->getCollection()->map(function ($row) {
        return [
            'month' => (int) $row->month,
            'year'  => (int) $row->year,
            'total_statements' => (int) $row->total_statements,
            'month_name' => date('F', mktime(0, 0, 0, $row->month, 1)), // optional helper
        ];
    })->values();

    // ✅ Always return with "data" key
    return response()->json([
        'data' => $data,
        'meta' => [
            'paging' => [
                'total'        => $statements->total(),
                'count'        => $statements->count(),
                'per_page'     => $statements->perPage(),
                'current_page' => $statements->currentPage(),
                'total_pages'  => $statements->lastPage(),
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

    $fileName = "bank_statements_{$month}_{$year}.xlsx";
    $filePath = "exports/{$fileName}";

    // Store the file in storage/app/public/exports
    Excel::store(new BankStatementsExport($month, $year), $filePath, 'public');

    return response()->json([
        'download_url' => asset("storage/{$filePath}"),
        'filename' => $fileName,
    ]);
}

}
