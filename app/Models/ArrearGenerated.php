<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ArrearGenerated extends BaseModel

{
    protected $table = 'arrears_generated';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'employee_id', 'created_at'];

    protected $appends = ['xid', 'x_employee_id'];

    protected $hashableGetterFunctions = [
        'getXEmployeeIdAttribute' => 'employee_id',
    ];

    // protected $fillable = [
    //     'employee_id',
    //     'emp_code',
    //     'month',
    //     'year',
    //     'arrear_amount',
    //     'basic',
    //     'hra',
    //     'allowance',
    //     'food_allowance',
    //     'total_earnings',
    //     'pf_employee',
    //     'esi_employee',
    //     'professional_tax',
    //     'tds',
    //     'total_contributions',
    //     'total_taxes_deductions',
    //     'net_salary',
    //     'total_working_days',
    //     'loss_of_pay_days',
    //     'days_payable',
    // ];

    public $timestamps = true;

    protected $casts = [
        'arrear_amount' => 'float',
        'basic' => 'float',
        'hra' => 'float',
        'allowance' => 'float',
        'food_allowance' => 'float',
        'total_earnings' => 'float',
        'pf_employee' => 'float',
        'esi_employee' => 'float',
        'professional_tax' => 'float',
        'tds' => 'float',
        'total_contributions' => 'float',
        'total_taxes_deductions' => 'float',
        'net_salary' => 'float',
    ];

      public function employee(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'employee_id');
    }
}
