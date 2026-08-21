<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Controllers\Controller;
use App\Models\EmpAdvance;
use Carbon\Carbon;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class EmpAdvanceController extends ApiBaseController
{
    protected $model = EmpAdvance::class;

protected function modifyIndex($query)
{
    return $query
        ->leftJoin('users as employee', 'employee.id', '=', 'emp_advances.employee_id')
        ->select('emp_advances.*')
        ->with('employee');
}


public function store()
{
    $request = request(); 

    $validated = $request->validate([
        'employee_id'   => 'required|exists:users,id',
        'advance_type'  => 'required|in:salary_advance,site_advance',
        'amount'        => 'required|numeric|min:0',
        'deduct_month'  => 'required|date_format:Y-m',
    ]);

    $advance = EmpAdvance::create($validated);

    return response()->json([
        'message' => 'Advance created successfully.',
        'data'    => $advance,
        'xid'     => $advance->id,
    ], 201);
}
 


}