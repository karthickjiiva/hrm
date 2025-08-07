<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Loans\IndexRequest;;
use App\Http\Requests\Api\Loans\DeleteRequest;
use App\Models\EmployeeLoan; 
use App\Models\EmployeeLoanRepayment;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EmployeeLoanController extends ApiBaseController
{
    protected $model = EmployeeLoan::class;

    protected $indexRequest = IndexRequest::class;
    protected $deleteRequest = DeleteRequest::class;

 protected function modifyIndex($query)
{
    return $query->with('employee');
}

public function store()
{
    $request = request(); 

    $validated = $request->validate([
        'employee_id'     => 'required',
        'amount'          => 'required|numeric|min:1',
        'tenure'          => 'required|integer|min:1|max:12',
        'monthly_amount'  => 'required|numeric|min:1', 
        'start_month'     => 'required|date_format:Y-m',
        'end_month'       => 'required|date_format:Y-m',
    ]);     

    // Store the loan
    $loan = EmployeeLoan::create($validated);

    // Generate repayment records
    $monthlyAmount = round($loan->amount / $loan->tenure, 2);
    $start = Carbon::parse($loan->start_month)->startOfMonth();

    for ($i = 0; $i < $loan->tenure; $i++) {
        $repaymentMonth = $start->copy()->addMonths($i);

        $loan->repayments()->create([
            'repayment_month' => $repaymentMonth->format('Y-m-d'),
            'amount'          => $monthlyAmount,
            'status'          => 'pending',
            'is_final'        => $i === ($loan->tenure - 1),
        ]);
    }

    return response()->json([
        'message' => 'Loan saved successfully.',
        'data'    => $loan,
        'xid'     => $loan->id,
    ], 201);
}

 
public function update(...$args)
{
    $xid = $args[0];
    $id = Hashids::decode($xid)[0] ?? null;

    if (!$id) {
        return response()->json([
            'message' => 'Invalid ID',
        ], 404);
    }

    $request = request();

    $validated = $request->validate([
        'employee_id'     => 'required',
        'amount'          => 'required|numeric|min:1',
        'tenure'          => 'required|integer|min:1|max:12',
        'monthly_amount'  => 'required|numeric|min:1', 
        'start_month'     => 'required|date_format:Y-m',
        'end_month'       => 'required|date_format:Y-m',
    ]);

    $loan = EmployeeLoan::findOrFail($id);

DB::transaction(function () use ($loan, $validated) {
    $loan->update($validated);

    $existingRepayments = $loan->repayments()->get()->keyBy(function ($repayment) {
        return Carbon::parse($repayment->repayment_month)->format('Y-m');
    });

    $monthlyAmount = round($loan->amount / $loan->tenure, 2);
    $start = Carbon::parse($loan->start_month)->startOfMonth();

    $newRepayments = [];

    for ($i = 0; $i < $loan->tenure; $i++) {
        $repaymentMonth = $start->copy()->addMonths($i);
        $monthKey = $repaymentMonth->format('Y-m');

        if ($existingRepayments->has($monthKey)) {
            // Update amount & is_final, but preserve status
            $repayment = $existingRepayments[$monthKey];
            $repayment->update([
                'amount'     => $monthlyAmount,
                'is_final'   => $i === ($loan->tenure - 1),
            ]);
            $existingRepayments->forget($monthKey);
        } else {
            // New month — create
            $newRepayments[] = [
                'repayment_month' => $repaymentMonth->format('Y-m-d'),
                'amount'          => $monthlyAmount,
                'status'          => 'pending',
                'is_final'        => $i === ($loan->tenure - 1),
            ];
        }
    }

    // Delete any repayments that were outside the updated tenure and not paid/skipped
    foreach ($existingRepayments as $repayment) {
        if (in_array($repayment->status, ['pending'])) {
            $repayment->delete();
        }
        // else: keep it if it's paid/skipped
    }

    // Insert new ones
    $loan->repayments()->createMany($newRepayments);
});

    return response()->json([
        'message' => 'Loan updated successfully.',
        'data'    => $loan,
        'xid'     => $loan->id,
    ]);
}


 
public function getRepayments($xid)
{
    // Decode XID
    $decoded = Hashids::decode($xid);
    $id = $decoded[0] ?? null;

    if (!$id) {
        abort(404, 'Invalid ID');
    }

    // Fetch the loan with repayments
    $loan = EmployeeLoan::with('repayments')->findOrFail($id); 

    $repayments = $loan->repayments->map(function ($repayment) {
        return [
            'id' => $repayment->id,
            'repayment_month' => $repayment->repayment_month->format('Y-m'),
            'amount' => $repayment->amount,
            'status' => $repayment->status,
        ];
    });

    return response()->json([
        'data' => $repayments,
    ]);
}

public function skipMonth($id)
{
    $repayment = EmployeeLoanRepayment::findOrFail($id);

    $currentMonth = Carbon::now()->format('Y-m');
    $repaymentMonth = Carbon::parse($repayment->repayment_month)->format('Y-m');

    // ✅ Rule checks
    if ($repayment->status !== 'pending') {
        return response()->json(['error' => 'Only pending repayments can be skipped.'], 400);
    }

    if ($repaymentMonth !== $currentMonth) {
        return response()->json(['error' => 'Only current month repayment can be skipped.'], 400);
    }

    // ❌ Skip this one
    $repayment->status = 'skipped';
    $repayment->save();

    // Get loan
    $loan = $repayment->loan;

    // Get last repayment
    $lastRepayment = $loan->repayments()->orderBy('repayment_month', 'desc')->first();

    // Remove is_final from current final
    if ($lastRepayment && $lastRepayment->is_final) {
        $lastRepayment->is_final = false;
        $lastRepayment->save();
    }

    // Add new repayment
    $newRepaymentMonth = Carbon::parse($lastRepayment->repayment_month)->addMonth()->startOfMonth();

    $loan->repayments()->create([
        'repayment_month' => $newRepaymentMonth,
        'amount' => $repayment->amount,  
        'status' => 'pending',
        'is_final' => true,
    ]);
    $loan->end_month = $newRepaymentMonth->format('Y-m');
    $loan->save();

    return response()->json(['message' => 'Repayment skipped and month added.']);
}

public function closeLoan(Request $request, $xid)
{
    $id = Hashids::decode($xid)[0] ?? null;
    $loan = EmployeeLoan::with('repayments')->findOrFail($id);

    $pendingRepayments = $loan->repayments->where('status', 'pending');
    $outstandingAmount = $pendingRepayments->sum('amount');
    $isForceClose = $request->boolean('force');

    // Normal Close (no pending)
    // if (!$isForceClose && $outstandingAmount > 0) {
    //     return response()->json([
    //         'status' => 'error',
    //         'message' => 'Loan cannot be closed. Outstanding amount: ' . $outstandingAmount,
    //     ], 422);
    // }

    $loan->status = $isForceClose ? 'foreclosed' : 'closed';
    $loan->save();

    $date = now()->format('Y-m-d');
    $realremark = $isForceClose 
        ? "Force closed on {$date}" 
        : "Loan closed on {$date}";

  
        foreach ($pendingRepayments as $repayment) {
            $repayment->update([
                'status' => 'paid',
                'remarks' => $realremark,
            ]);
        }
    
    return response()->json([
        'status' => 'success',
        'message' => 'Loan has been successfully ' . ($isForceClose ? 'force closed' : 'closed'),
        'outstanding' => $outstandingAmount,
    ]);
}



}
