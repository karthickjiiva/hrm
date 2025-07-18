<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveAdjustment extends BaseModel
{
    protected $table = 'leave_adjustments';

    protected $default = ['xid', 'employee_id', 'payroll_id', 'type', 'month', 'year'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'employee_id', 'payroll_id'];

    protected $appends = ['xid', 'x_employee_id', 'x_payroll_id'];

    protected $filterable = ['type', 'month', 'year', 'employee_id', 'payroll_id'];

    protected $hashableGetterFunctions = [
        'getXEmployeeIdAttribute' => 'employee_id',
        'getXPayrollIdAttribute' => 'payroll_id',
    ];

    protected $casts = [
        'type' => 'string', // 'credit' or 'deduction'
        'date' => 'date',
        'month' => 'integer',
        'year' => 'integer',
        'cl' => 'float',
        'sl' => 'float',
        'el' => 'float',
        'applied_to_payroll' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);

        // Automatically set month/year from payroll if payroll_id is set
        static::creating(function ($model) {
            if ($model->payroll_id && !$model->month && !$model->year) {
                $payroll = PayrollNew::find($model->payroll_id);
                if ($payroll) {
                    $model->month = $payroll->month;
                    $model->year = $payroll->year;
                }
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'employee_id');
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(PayrollNew::class, 'payroll_id');
    }
}