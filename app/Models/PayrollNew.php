<?php

namespace App\Models;

use App\Casts\Hash;
use App\Models\BaseModel;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollNew extends BaseModel
{
    protected $table = 'payroll_new';

    protected $default = ['xid', 'employee_id', 'month', 'year', 'net_salary'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $hidden = ['id', 'employee_id', 'created_by'];

    protected $appends = ['xid', 'x_employee_id', 'x_created_by', 'net_salary_in_words'];

    protected $filterable = ['month', 'year', 'employee_id'];

    protected $hashableGetterFunctions = [
        'getXEmployeeIdAttribute' => 'employee_id',
        'getXCreatedByAttribute' => 'created_by',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'actual_payable_days' => 'float',
        'total_working_days' => 'float',
        'loss_of_pay_days' => 'float',
        'days_payable' => 'float',
        'basic' => 'float',
        'hra' => 'float',
        'allowance' => 'float',
        'food_allowance' => 'float',
        'total_earnings' => 'float',
        'pf_employee' => 'float',
        'esi_employee' => 'float',
        'total_contributions' => 'float',
        'professional_tax' => 'float',
        'tds' => 'float',
        'total_taxes_deductions' => 'float',
        'net_salary' => 'float',
        'per_day_salary'=>'float',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new CompanyScope);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class, 'employee_id');
    }

    public function getNetSalaryInWordsAttribute()
    {
        if (!isset($this->attributes['net_salary'])) {
            return null;
        }
        
        return $this->numberToWords($this->attributes['net_salary']);
    }

    protected function numberToWords($number)
    {
        // Implement your number to words conversion logic here
        // This is a placeholder - you'll need to implement or use a package
        return "Amount in words placeholder for " . $number;
    }
}