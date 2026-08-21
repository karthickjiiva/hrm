<?php

namespace App\Http\Controllers\Api;

use App\Classes\Common;
use App\Http\Controllers\ApiBaseController;
use App\Models\Company;
use App\Models\GratuityForm;
use App\Models\PayrollNew;
use App\Models\StaffMember;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Gratuity claim form (LIC Group Gratuity – Cash Accumulation Scheme).
 *
 * Generates the 2-page PDF from resources/views/pdf/gratuity_form.blade.php for a
 * resigned employee, stores it under storage/app/public/gratuity_forms and records it
 * in the gratuity_forms table so it can be listed / re-downloaded later.
 */
class GratuityFormController extends ApiBaseController
{
    /* ---- Scheme constants printed on the form. Change to match the company's LIC policy. ---- */
    const POLICY_TITLE   = 'New Proposal Master Police No is: 605011811, date:30.05.2022';
    const TRUST_NAME     = 'N.VENKATARAMAN EMPLOYEES GRATUITY TRUST FUNDS …';
    const TRUST_ADDRESS  = '..No.192, North Usman Road, T.Nagar, Chennai 600 017';
    const GRATUITY_RATE  = 'Gratuity Rate : 15 days wages per year of Service';
    const RETIREMENT_AGE = 58;
    const DEFAULT_PLACE  = 'Chennai';
    const STORAGE_DIR    = 'gratuity_forms';

    /**
     * GET gratuity-forms/resigned-employees
     * Employees flagged has_resigned = 1 (and not rejoined), for the select box.
     */
    public function resignedEmployees()
    {
        $employees = StaffMember::where('has_resigned', 1)
            ->where(function ($q) {
                $q->whereNull('has_rejoined')->orWhere('has_rejoined', 0);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'employee_number', 'joining_date', 'resignation_date', 'end_date']);

        $data = $employees->map(function ($emp) {
            return [
                'xid'              => Common::getHashFromId($emp->id),
                'name'             => $emp->name,
                'employee_number'  => $emp->employee_number,
                'joining_date'     => $emp->joining_date ? Carbon::parse($emp->joining_date)->format('Y-m-d') : null,
                'resignation_date' => $emp->resignation_date ? Carbon::parse($emp->resignation_date)->format('Y-m-d') : null,
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * GET gratuity-forms?limit=10&page=1
     * Paginated history of generated forms.
     */
    public function index(Request $request)
    {
        $forms = GratuityForm::with(['employee'])
            ->orderBy('id', 'desc')
            ->paginate($request->get('limit', 10));

        return response()->json($forms);
    }

    /**
     * POST gratuity-forms/generate  { employee_id: xid, salary_on_exit?: number }
     * Builds the form data, renders the PDF, stores it and records it.
     */
    public function generate(Request $request)
    {
        $request->validate([
            'employee_id'    => 'required|string',
            'salary_on_exit' => 'nullable|numeric|min:0',
        ]);

        $employeeId = $this->getIdFromHash($request->employee_id);
        $employee   = StaffMember::with(['location'])->findOrFail($employeeId);

        $company = company() ?: Company::first();
        $fields  = $this->buildFields($employee, $company, $request->salary_on_exit);

        $pdf = Pdf::loadView('pdf.gratuity_form', ['f' => $fields])->setPaper('letter', 'portrait');

        $filename = 'Gratuity_Form_' . Str::slug($employee->name, '_') . '_' . date('Ymd_His') . '.pdf';
        $filePath = self::STORAGE_DIR . '/' . $filename;
        Storage::disk('public')->put($filePath, $pdf->output());

        $form = GratuityForm::create([
            'company_id'      => $company ? Common::getIdFromHash($company->xid) : null,
            'employee_id'     => $employee->id,
            'filename'        => $filename,
            'file_path'       => $filePath,
            'date_of_joining' => $fields['_meta']['date_of_joining'],
            'exit_date'       => $fields['_meta']['exit_date'],
            'total_service'   => $fields['total_service'],
            'salary_on_exit'  => $fields['_meta']['salary'],
            'gratuity_amount' => $fields['_meta']['gratuity'],
            'form_data'       => $fields,
            'created_by'      => auth('api')->id(),
        ]);

        $form->load('employee');

        return response()->json([
            'message' => 'Gratuity form generated successfully.',
            'data'    => $form,
        ]);
    }

    /**
     * GET gratuity-forms/{xid}/download
     * Streams the stored PDF; regenerates it from the saved form data if the file is gone.
     */
    public function download($xid)
    {
        $form = GratuityForm::findOrFail($this->getIdFromHash($xid));

        if (Storage::disk('public')->exists($form->file_path)) {
            return response()->download(Storage::disk('public')->path($form->file_path), $form->filename);
        }

        if (!empty($form->form_data)) {
            $pdf = Pdf::loadView('pdf.gratuity_form', ['f' => $form->form_data])->setPaper('letter', 'portrait');
            return $pdf->download($form->filename);
        }

        abort(404, 'Gratuity form file not found');
    }

    /**
     * DELETE gratuity-forms/{xid}
     */
    public function destroy($xid)
    {
        $form = GratuityForm::findOrFail($this->getIdFromHash($xid));

        if (Storage::disk('public')->exists($form->file_path)) {
            Storage::disk('public')->delete($form->file_path);
        }
        $form->delete();

        return response()->json(['message' => 'Gratuity form deleted successfully.']);
    }

    /* ======================================================================
     |  Helpers
     * ====================================================================== */

    /**
     * Build the $f array consumed by pdf/gratuity_form.blade.php.
     * Keys are documented at the top of that Blade file. '_meta' holds raw values for the DB row.
     */
    protected function buildFields($employee, $company, $salaryOverride = null): array
    {
        $today = Carbon::today();
        $doj   = $employee->joining_date ? Carbon::parse($employee->joining_date)->startOfDay() : null;
        $dob   = $employee->dob ? Carbon::parse($employee->dob)->startOfDay() : null;
        $exit  = $employee->resignation_date
            ? Carbon::parse($employee->resignation_date)->startOfDay()
            : ($employee->end_date ? Carbon::parse($employee->end_date)->startOfDay() : $today);

        // Length of service
        $years = $months = 0;
        if ($doj && $exit->greaterThan($doj)) {
            $years  = (int) floor($doj->diffInYears($exit));
            $months = (int) floor($doj->copy()->addYears($years)->diffInMonths($exit));
        }
        // Payment of Gratuity Act: a part of a year beyond 6 months counts as a full year
        $serviceYears = $months >= 6 ? $years + 1 : $years;

        // Salary (Basic) as on date of exit
        $salary   = $salaryOverride !== null && $salaryOverride !== '' ? (float) $salaryOverride : $this->lastDrawnBasic($employee);
        $gratuity = (int) round($salary * 15 * $serviceYears / 26);

        $salaryStr = $this->num($salary);
        $addr      = $this->splitAddress($company->address ?? '');
        $place     = $employee->location->name ?? self::DEFAULT_PLACE;

        return [
            'policy_title'        => self::POLICY_TITLE,
            'company_name'        => strtoupper($company->name ?? ''),
            'address_line1'       => $addr[0],
            'address_line2'       => $addr[1],
            'address_line3'       => $addr[2],
            'member_name'         => strtoupper($employee->name),
            'entry_date'          => $doj ? $doj->format('d-m-Y') : '',
            'place_of_work'       => 'place of Work ' . $place,
            'date_of_birth'       => $dob ? $dob->format('d-m-Y') : '',
            'retirement_age'      => ': ' . self::RETIREMENT_AGE . ' yrs',
            'date_of_joining'     => $doj ? $doj->format('d-m-Y') : '',
            'scheme_entry_date'   => $doj ? $doj->format('d-m-Y') : '',
            'exit_date'           => $exit->format('d-m-Y'),
            'exit_date_a'         => $exit->format('d-m-Y'),
            'exit_cause'          => 'Resigned',
            'gratuity_rate'       => self::GRATUITY_RATE,
            'total_service'       => $years . ' Yrs ' . $months . ' Months',
            'retirement_age_b'    => ': ' . self::RETIREMENT_AGE . ' yrs',
            'salary_on_exit'      => $salaryStr . '/-',
            'gratuity_formula'    => $salaryStr . ' X 15 X ' . $serviceYears . ' = ' . $gratuity,
            'gratuity_divisor'    => '26',
            'gratuity_applicable' => 'Rs.' . $gratuity . '/-',
            'place'               => $place,
            'date'                => 'Date:' . $today->format('d/m/Y'),
            'trust_name'          => self::TRUST_NAME,
            'trust_address'       => self::TRUST_ADDRESS,
            'amount'              => (string) $gratuity,
            'amount_in_words'     => $this->amountInWords($gratuity),
            'membership_no'       => (string) ($employee->employee_number ?? ''),
            'benefit_type'        => 'Resigned',
            'day_ordinal'         => 'this ' . $today->format('jS'),
            'month_year'          => $today->format('M-y'),
            '_meta'               => [
                'date_of_joining' => $doj ? $doj->format('Y-m-d') : null,
                'exit_date'       => $exit->format('Y-m-d'),
                'salary'          => $salary,
                'gratuity'        => $gratuity,
                'service_years'   => $serviceYears,
            ],
        ];
    }

    /** Basic from the latest payroll run, falling back to the employee's salary settings. */
    protected function lastDrawnBasic($employee): float
    {
        $payroll = PayrollNew::where('employee_id', $employee->id)
            ->orderBy('year', 'desc')->orderBy('id', 'desc')
            ->first();

        if ($payroll && (float) $payroll->basic > 0) {
            return (float) $payroll->basic;
        }

        return (float) ($employee->basic_salary ?: ($employee->monthly_amount ?: 0));
    }

    /** Split a free-text address into 3 lines for the form. */
    protected function splitAddress(string $address): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', $address)), 'strlen'));
        $lines = ['', '', ''];
        if (!$parts) {
            return $lines;
        }
        if (count($parts) <= 3) {
            foreach ($parts as $i => $p) {
                $lines[$i] = $p . ($i < count($parts) - 1 ? ',' : '');
            }
            return $lines;
        }
        $chunks = array_chunk($parts, (int) ceil(count($parts) / 3));
        foreach (array_slice($chunks, 0, 3) as $i => $chunk) {
            $lines[$i] = implode(', ', $chunk) . ($i < 2 ? ',' : '');
        }
        return $lines;
    }

    /** Number formatted without trailing zeros: 30114 / 30114.5 */
    protected function num($n): string
    {
        $s = number_format((float) $n, 2, '.', '');
        return rtrim(rtrim($s, '0'), '.');
    }

    /** "Rupees One Lakh Seventy Three Thousand Seven Hundred and Thirty Five Only" (Indian numbering). */
    protected function amountInWords(int $amount): string
    {
        if ($amount <= 0) {
            return 'Rupees Zero Only';
        }
        return 'Rupees ' . trim($this->toWords($amount)) . ' Only';
    }

    protected function toWords(int $n): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve',
            'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $below100 = function (int $x) use ($ones, $tens) {
            if ($x < 20) {
                return $ones[$x];
            }
            return trim($tens[intdiv($x, 10)] . ' ' . $ones[$x % 10]);
        };

        $words = '';
        if ($n >= 10000000) {
            $words .= $this->toWords(intdiv($n, 10000000)) . ' Crore ';
            $n %= 10000000;
        }
        if ($n >= 100000) {
            $words .= $this->toWords(intdiv($n, 100000)) . ' Lakh ';
            $n %= 100000;
        }
        if ($n >= 1000) {
            $words .= $this->toWords(intdiv($n, 1000)) . ' Thousand ';
            $n %= 1000;
        }
        if ($n >= 100) {
            $words .= $ones[intdiv($n, 100)] . ' Hundred ';
            $n %= 100;
            if ($n > 0) {
                $words .= 'and ';
            }
        }
        if ($n > 0) {
            $words .= $below100($n) . ' ';
        }
        return trim($words);
    }
}
