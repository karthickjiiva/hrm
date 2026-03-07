<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\PayrollNew\IndexRequest;
use Examyou\RestAPI\ApiResponse;
use App\Models\PayrollNew;
use App\Models\Arrear;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf; 
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Company;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PayrollExport;
use App\Exports\ArrearPfExport; 
use App\Models\ArrearGenerated;
use App\Models\StaffMember;  
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


class ArrearsController extends ApiBaseController
{   

     protected $model = ArrearGenerated::class;

public function index()
{
    $request = request();
    $query = ArrearGenerated::with('employee')
        ->select('arrears_generated.*');
 
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

        $payroll = ArrearGenerated::with(['employee.designation','employee.department',])->findOrFail($id);     

        $company = Company::first();
        $logoUrl = $company->dark_logo_url;
        $defaultLogoPath = public_path('images/dark.png');
        $logoPath = $logoUrl === asset('images/dark.png')
            ? $defaultLogoPath
            : str_replace(asset('storage'), storage_path('app/public'), $logoUrl);        
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $data = file_get_contents($logoPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        } else {
            dd("File not found at: " . $logoPath); 
        }
        
        $pdf = Pdf::loadView('pdf.arrearslip', compact('payroll', 'company', 'logoBase64'));
        return $pdf->download("arrears-{$payroll->employee->name}.pdf");
    }

      public function pfexport(Request $request)
    {
        $month = $request->get('month');
        $year = $request->get('year');

        if (!$month || !$year) {
            return response()->json(['message' => 'Month and Year are required.'], 422);
        }

        try {
            $payrolls = ArrearGenerated::with(['employee.employeeType'])
                ->where('month', $month)
                ->where('year', $year)
                ->get();

            if ($payrolls->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data found for the selected month and year.'
                ], 404);
            }

            $filename = "arrear_pf_{$month}_{$year}.xlsx";
            $export = new ArrearPfExport($month, $year);
            $filePath = 'exports/' . $filename;
            Excel::store($export, $filePath, 'public');

            return response()->json([
                'success' => true,
                'download_url' => asset('storage/' . $filePath),
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function uploadArrears(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        try {
            $file = $request->file('file');

            $data = Excel::toCollection(null, $file)->first();

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The uploaded file is empty.',
                ], 400);
            }

            $insertData = [];

            foreach ($data as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $empCode = $row[0] ?? null;
                $name       = $row[1] ?? null;
                $amount     = $row[2] ?? null;

                if (!$empCode || !$name || !$amount) {
                    continue;
                }

                $insertData[] = [
                    'emp_code' => $empCode,
                    'name'        => $name,
                    'amount'      => $amount,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }

            if (!empty($insertData)) {
                Arrear::insert($insertData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Arrears data uploaded successfully.',
                'rows_inserted' => count($insertData),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function generateArrears(Request $request)
    {
        $month = (int) $request->get('month');
        $year  = (int) $request->get('year');

        if (!$month || !$year) {
            return response()->json(['success' => false, 'message' => 'Month and Year are required.'], 422);
        }

         $arrears = Arrear::all();

    if ($arrears->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No arrears data found. Please upload arrears file first.'
        ], 404);
    }

        $generated = [];
        $errors = [];

        foreach ($arrears as $arrear) {
            try {
                DB::beginTransaction();
                $record = $this->processArrear($arrear, $month, $year);
                DB::commit();
                $generated[] = $record->id;
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = [
                    'emp_code' => $arrear->emp_code ?? $arrear->customer_id ?? null,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $this->makeSuccessResponse('Arrears generated.', [
            'generated_count' => count($generated),
            'error_count' => count($errors),
            'errors' => $errors,
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
    /**
     * Process a single arrear row and save calculated splits into arrears_generated.
     *
     * @param  \App\Models\Arrear $arrear
     * @param  int $month
     * @param  int $year
     * @return \App\Models\ArrearGenerated
     *
     * @throws \Exception
     */
    protected function processArrear(Arrear $arrear, int $month, int $year)
    {
        // emp_code may be stored as emp_code or customer_id depending on your upload
        $empCode = $arrear->emp_code ?? $arrear->customer_id ?? null;
        if (!$empCode) {
            throw new \Exception('Missing emp_code in arrear row.');
        }

        // find employee by employee_number (emp_code)
        $employee = User::where('employee_number', $empCode)->first();
        if (!$employee) {
            throw new \Exception("Employee not found for emp_code: {$empCode}");
        }

        // get employee type (percentages)
        $employeeType = $employee->employeeType;  // assuming you have relation in User model
        if (!$employeeType) {
            throw new \Exception("Employee type not set for emp_code: {$empCode}");
        }


        // get payroll for this employee/month/year (to reuse days/payable info)
        $payroll = PayrollNew::where('employee_id', $employee->id)
                    ->where('month', $month)
                    ->where('year', $year)
                    ->first();

        if (!$payroll) {
            throw new \Exception("Payroll not found for {$month}-{$year}");
        }

        // days info
        $totalWorkingDays = (int) ($payroll->total_working_days ?? Carbon::create($year, $month)->daysInMonth);
        $actualPayableDays = (float) ($payroll->actual_payable_days ?? $payroll->days_payable ?? $totalWorkingDays);
        $lossOfPayDays = (float) ($payroll->loss_of_pay_days ?? 0);

        // source arrear amount
        $arrearAmount = (float) ($arrear->amount ?? $arrear->arrear_amount ?? 0);

        // prorate the arrear for payable days (if you consider arrearAmount as full-month gross)
        if ($totalWorkingDays > 0) {
            $totalEarnings = round(($arrearAmount / $totalWorkingDays) * $actualPayableDays, 2);
        } else {
            $totalEarnings = round($arrearAmount, 2);
        }

        // percentages (use null-coalesce to avoid undefined properties)
        $basicPercent = (float) ($employeeType->basic_percent ?? 0);
        $hraPercent = (float) ($employeeType->hra_percent ?? 0);
        $allowancePercent = (float) ($employeeType->allowance_percent ?? 0);
        $foodAllowancePercent = (float) ($employeeType->food_allowance_percent ?? 0);

        // component splits
        $basic = round($totalEarnings * ($basicPercent / 100), 2);
        $hra = round($totalEarnings * ($hraPercent / 100), 2);
        $allowance = round($totalEarnings * ($allowancePercent / 100), 2);
        $foodAllowance = round($totalEarnings * ($foodAllowancePercent / 100), 2);

        // PF (employee share)
        $pfEmployee = 0.0;
        if (!empty($employeeType->pf_enabled)) {
            if (!empty($employeeType->pf_fix_amount)) {
                // treat pf_percentage as fixed amount
                $pfEmployee = round((float) ($employeeType->pf_percentage ?? 0), 2);
            } else {
                // pf percentage applies on basic (common approach)
                $pfEmployee = round($basic * ((float) ($employeeType->pf_percentage ?? 0) / 100), 2);
            }
        }

        // ESI (employee share)
        $esiEmployee = 0.0;
        if (!empty($employeeType->esi_enabled)) {
            if (!empty($employeeType->esi_fix_amount)) {
                $esiEmployee = round((float) ($employeeType->esi_percentage ?? 0), 2);
            } else {
                $esiEmployee = round($totalEarnings * ((float) ($employeeType->esi_percentage ?? 0) / 100), 2);
            }
        }

        // Professional tax
        $professionalTax = 0.0;
        // if (!empty($employeeType->prof_tax_enabled)) {
        //     if (!empty($employeeType->prof_tax_fix_amount)) {
        //         $professionalTax = round((float) ($employeeType->prof_tax_percentage ?? 0), 2);
        //     } else {
        //         $professionalTax = round($totalEarnings * ((float) ($employeeType->prof_tax_percentage ?? 0) / 100), 2);
        //     }
        // }

        // TDS
        $tds = 0.0;
        if (!empty($employeeType->tds_enabled)) {
            if (!empty($employeeType->tds_fix_amount)) {
                $tds = round((float) ($employeeType->tds_percentage ?? 0), 2);
            } else {
                $tds = round($totalEarnings * ((float) ($employeeType->tds_percentage ?? 0) / 100), 2);
            }
        }

        $totalContributions = round($pfEmployee + $esiEmployee, 2);
        $totalTaxesDeductions = round($professionalTax + $tds, 2);
        $netSalary = round($totalEarnings - $totalContributions - $totalTaxesDeductions, 2);

        $data = [
            'employee_id' => $employee->id,
            'emp_code' => $empCode,
            'month' => $month,
            'year' => $year,
            'arrear_amount' => $arrearAmount,
            'basic' => $basic,
            'hra' => $hra,
            'allowance' => $allowance,
            'food_allowance' => $foodAllowance,
            'total_earnings' => $totalEarnings,
            'pf_employee' => $pfEmployee,
            'esi_employee' => $esiEmployee,
            'professional_tax' => $professionalTax,
            'tds' => $tds,
            'total_contributions' => $totalContributions,
            'total_taxes_deductions' => $totalTaxesDeductions,
            'net_salary' => $netSalary,
            'total_working_days' => $totalWorkingDays,
            'loss_of_pay_days' => $lossOfPayDays,
            'days_payable' => $actualPayableDays,
        ];

        // Insert or update (one record per employee/month/year)
        $record = ArrearGenerated::updateOrCreate(
            ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
            $data
        );

        return $record;
    }
}